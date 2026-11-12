<?php

namespace App\Http\Livewire\Order;

use App\Http\Resources\UserResource;
use App\Models\ArtworkRequired;
use App\Models\Order as OrderModel;
use App\Models\OrderArtworkFile;
use App\Models\OrderFieldValue;
use App\Models\Product;
use App\Models\User;
use App\Services\Proofing\EmailService;
use App\Services\Proofing\StatusService;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;

class Order extends Component
{
    use WithFileUploads;

    // --- Previous implementation: a Zoho Forms iframe for ID card ordering.
    // Kept here, commented out, per request rather than deleted outright.
    // The view (resources/views/livewire/order.blade.php) has the matching
    // iframe markup commented out alongside this.
    //
    // public $school;
    //
    // public function mount()
    // {
    //     if (!Auth::user()->isSchoolAdmin()) {
    //         return redirect()->route('dashboard');
    //     }
    // }
    //
    // public function render()
    // {
    //     /** @var User $user */
    //     $user = Auth::user();
    //
    //     return view('livewire.order',
    //         [
    //             'user' => $user,
    //             'schoolKey' => $user->getSchool()->schoolkey,
    //
    //         ])
    //         ->layout('layouts.authenticated', [
    //             'user' => new UserResource(Auth::user()),
    //         ]);
    // }

    // Accepted file types for the artwork upload dropzone: images, Word
    // documents, and PDFs.
    public const ARTWORK_UPLOAD_MIMES = 'jpg,jpeg,png,gif,webp,bmp,pdf,doc,docx';

    // Livewire's own temporary-upload validation (config/livewire.php) is
    // what actually enforces the 15MB ceiling before this ever reaches our
    // rules below - this constant just keeps both places' numbers in sync
    // and readable.
    public const ARTWORK_UPLOAD_MAX_KB = 15360; // 15MB, in KB

    /**
     * false: this page lists the Promotional Products (/order); true: only the
     * products flagged is_school_product (/school-products, see SchoolProducts).
     */
    protected bool $schoolProducts = false;

    protected string $pageTitle = 'Promotional Products';

    public $products = [];
    public $selectedProductId = null;
    public $customFields = [];

    /** @var array<int, mixed> custom_field_id => submitted text/number/choice value */
    public array $fieldValues = [];

    /** @var array<int, string> custom_field_id => typed "please specify" text when the "Other" option is selected on a radio/dropdown field */
    public array $fieldOtherValues = [];

    // --- Artwork section - shown after the product's own fields, the same
    // on every product's order form.
    /** @var int|string selected artworks_required.id */
    public $artworkRequiredId = '';

    /** @var \Illuminate\Support\Collection<int, ArtworkRequired> */
    public $artworkRequiredOptions = [];

    public string $artworkDueDate = ''; // customer-entered, d/m/Y (via the flatpickr calendar)
    public string $artworkBrief = ''; // HTML from the rich text editor
    public string $additionalComments = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $artworkFiles = [];

    /**
     * Landing spot for the file input. Livewire replaces whatever a
     * wire:model'd file input is bound to on every upload, so the input is
     * bound to this scratch property and each batch is appended to
     * $artworkFiles (see updatedNewArtworkFiles) - that way earlier files stay
     * until the customer clicks their remove icon.
     *
     * @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile>
     */
    public $newArtworkFiles = [];

    public string $successMessage = '';
    public ?string $lastOrderNumber = null;

    public function mount()
    {
        if (!Auth::user()->isSchoolAdmin()) {
            return redirect()->route('dashboard');
        }

        $this->products = Product::where('is_active', true)
            ->where('is_school_product', $this->schoolProducts)
            ->orderBy('sort_order')->orderBy('name')->get();
        $this->artworkRequiredOptions = ArtworkRequired::orderBy('id')->get();
    }

    /** @var array{0: int|string|null, 1: array<int, string[]>}|null */
    protected ?array $allowedOptionMapMemo = null;

    /**
     * Option limits of the selected product: [custom_field_id => [option_value, ...]].
     * A field that is not listed shows all of its options.
     *
     * @return array<int, string[]>
     */
    protected function allowedOptionMap(): array
    {
        if ($this->allowedOptionMapMemo === null || $this->allowedOptionMapMemo[0] !== $this->selectedProductId) {
            $this->allowedOptionMapMemo = [
                $this->selectedProductId,
                $this->selectedProductId ? Product::allowedOptionMapFor((int) $this->selectedProductId) : [],
            ];
        }

        return $this->allowedOptionMapMemo[1];
    }

    /**
     * The options of a radio / dropdown / checkbox field that the selected
     * product shows (all of them unless the product limits the field).
     */
    public function optionsFor($field)
    {
        $allowed = $this->allowedOptionMap()[$field->id] ?? null;

        if ($allowed === null) {
            return $field->fieldOptions;
        }

        return $field->fieldOptions
            ->filter(fn ($option) => in_array((string) $option->option_value, $allowed, true))
            ->values();
    }

    public function selectProduct($productId): void
    {
        $this->selectedProductId = $productId;
        $this->refreshFieldsForSelectedProduct();
        $this->dispatch('order-scroll-top');
    }

    public function changeProduct(): void
    {
        $this->selectedProductId = null;
        $this->refreshFieldsForSelectedProduct();
        $this->dispatch('order-scroll-top');
    }

    /**
     * Whether the selected product's form has the Artwork section. When it
     * does not, the form ends with an Expected Delivery Date and
     * "Any other details?" instead (saved in the same order columns).
     */
    public function showsArtwork(): bool
    {
        $product = collect($this->products)->firstWhere('id', $this->selectedProductId);

        return !$product || $product->show_artwork;
    }

    /**
     * Starts fields flagged "prefill from user" with the logged-in user's
     * details (custom_fields.prefill_source). The customer can still edit them.
     */
    private function prefillFieldValues(): void
    {
        $user = Auth::user();

        foreach ($this->customFields as $field) {
            // Fields with a default value start with it (a disabled one keeps it).
            if ($field->isTextLike() && (string) $field->default_value !== '') {
                $this->fieldValues[$field->id] = $field->default_value;
            }

            if ($field->is_disabled || !$field->prefill_source) {
                continue;
            }

            $value = $field->prefillValueFor($user);

            // Don't prefill an option this product doesn't offer.
            $allowed = $this->allowedOptionMap()[$field->id] ?? null;
            if ($value !== null && $allowed !== null && !in_array((string) $value, $allowed, true)) {
                $value = null;
            }

            if ($value !== null) {
                $this->fieldValues[$field->id] = $value;
            }
        }
    }

    public function updatedSelectedProductId(): void
    {
        $this->refreshFieldsForSelectedProduct();
    }

    public function updatedNewArtworkFiles(): void
    {
        $incoming = is_array($this->newArtworkFiles) ? $this->newArtworkFiles : [$this->newArtworkFiles];

        foreach ($incoming as $file) {
            if ($file) {
                $this->artworkFiles[] = $file;
            }
        }

        $this->artworkFiles = array_values($this->artworkFiles);
        $this->newArtworkFiles = [];
    }

    public function removeArtworkFile(int $index): void
    {
        unset($this->artworkFiles[$index]);
        $this->artworkFiles = array_values($this->artworkFiles);
    }

    /**
     * Abandon the order being filled in and go back to a clean product
     * picker - unlike changeProduct(), this also clears the Artwork
     * section, since "Cancel" means discarding the whole draft, not just
     * swapping which product it's for.
     */
    public function cancel(): void
    {
        $this->selectedProductId = null;
        $this->customFields = [];
        $this->fieldValues = [];
        $this->fieldOtherValues = [];
        $this->artworkRequiredId = '';
        $this->artworkDueDate = '';
        $this->artworkBrief = '';
        $this->additionalComments = '';
        $this->artworkFiles = [];
        $this->successMessage = '';
        $this->resetErrorBag();
    }

    private function refreshFieldsForSelectedProduct(): void
    {
        $this->fieldValues = [];
        $this->fieldOtherValues = [];
        $this->resetErrorBag();
        $this->successMessage = '';

        if (!$this->selectedProductId) {
            $this->customFields = [];
            return;
        }

        $product = Product::with('customFields.fieldOptions')->find($this->selectedProductId);
        $this->customFields = $product ? $product->customFields : [];

        $this->artworkDueDate = '';
        $this->additionalComments = '';

        $this->prefillFieldValues();
    }

    /**
     * @return array<string, mixed>
     */
    protected function fieldRules(): array
    {
        $rules = [
            // only products this page lists (a hand-made request can't order from the other page)
            'selectedProductId' => ['required', \Illuminate\Validation\Rule::in(collect($this->products)->pluck('id')->all())],
        ];

        $availableFieldIds = collect($this->customFields)->pluck('id')->all();

        foreach ($this->customFields as $field) {
            if (!$field->isVisibleGiven($this->fieldValues, $availableFieldIds)) {
                // Hidden by a "show only when" dependency that isn't
                // currently satisfied - not applicable, so no rules.
                continue;
            }

            $requiredRule = $field->is_required ? 'required' : 'nullable';

            if ($field->type === \App\Models\CustomField::TYPE_IMAGE) {
                // Display-only: the image is attached to the field
                // definition by the admin, the customer submits nothing here.
                continue;
            }

            if ($field->prefill_source === 'email') {
                $rules["fieldValues.{$field->id}"] = $requiredRule . '|email|max:255';
            } elseif ($field->type === \App\Models\CustomField::TYPE_TEL) {
                // digits with the usual phone punctuation: + ( ) - and spaces
                $rules["fieldValues.{$field->id}"] = [$requiredRule, 'string', 'max:30', 'regex:/^[+(]?[0-9][0-9\s().-]{5,}$/'];
            } elseif ($field->type === \App\Models\CustomField::TYPE_NUMBER) {
                $rules["fieldValues.{$field->id}"] = $requiredRule . '|numeric';
            } elseif ($field->type === \App\Models\CustomField::TYPE_CHECKBOX) {
                // A checkbox field lets more than one option be ticked at
                // once, so it's an array here rather than a single scalar -
                // "required" on an array already fails on an empty one, so
                // it doubles as "at least one option must be checked".
                $rules["fieldValues.{$field->id}"] = $requiredRule . '|array';
            } else {
                $rules["fieldValues.{$field->id}"] = $requiredRule . '|string|max:2000';
            }

            // The product may offer only some of the field's options: reject
            // anything else, even if the request was crafted by hand.
            $allowedOptions = $this->allowedOptionMap()[$field->id] ?? null;
            if ($allowedOptions !== null) {
                if ($field->type === \App\Models\CustomField::TYPE_CHECKBOX) {
                    $rules["fieldValues.{$field->id}.*"] = [\Illuminate\Validation\Rule::in($allowedOptions)];
                } elseif (in_array($field->type, [\App\Models\CustomField::TYPE_RADIO, \App\Models\CustomField::TYPE_DROPDOWN], true)) {
                    $rule = $rules["fieldValues.{$field->id}"];
                    $rule = is_string($rule) ? explode('|', $rule) : $rule;
                    $rules["fieldValues.{$field->id}"] = array_merge($rule, [\Illuminate\Validation\Rule::in($allowedOptions)]);
                }
            }

            $otherValue = $field->otherOptionValue();
            if ($otherValue !== null) {
                if ($field->type === \App\Models\CustomField::TYPE_CHECKBOX) {
                    // required_if can't express "this array field contains
                    // value X", so check it with a closure instead.
                    $rules["fieldOtherValues.{$field->id}"] = [
                        'nullable', 'string', 'max:2000',
                        function ($attribute, $value, $fail) use ($field, $otherValue) {
                            $selected = (array) ($this->fieldValues[$field->id] ?? []);
                            if (in_array($otherValue, $selected, true) && trim((string) $value) === '') {
                                $fail('This field is required.');
                            }
                        },
                    ];
                } else {
                    $rules["fieldOtherValues.{$field->id}"] = 'nullable|string|max:2000|required_if:fieldValues.' . $field->id . ',' . $otherValue;
                }
            }
        }

        return array_merge($rules, $this->showsArtwork() ? $this->artworkRules() : $this->deliveryRules());
    }

    /**
     * Rules for products without an Artwork section: an optional expected
     * delivery date and optional extra details.
     *
     * @return array<string, mixed>
     */
    protected function deliveryRules(): array
    {
        return [
            'artworkDueDate' => [
                'nullable',
                'date_format:d/m/Y',
                function ($attribute, $value, $fail) {
                    if ($value === null || $value === '') {
                        return;
                    }
                    try {
                        $date = Carbon::createFromFormat('d/m/Y', $value)->startOfDay();
                    } catch (\Exception $e) {
                        return;
                    }
                    if ($date->lessThanOrEqualTo(Carbon::today())) {
                        $fail('The expected delivery date must be a date in the future.');
                    }
                },
            ],
            'additionalComments' => 'nullable|string|max:5000',
        ];
    }

    /**
     * The fixed "Artwork" section rules - the same for every product.
     *
     * @return array<string, mixed>
     */
    protected function artworkRules(): array
    {
        return [
            'artworkRequiredId' => 'required|exists:artworks_required,id',
            'artworkDueDate' => [
                'required',
                'date_format:d/m/Y',
                function ($attribute, $value, $fail) {
                    try {
                        $date = Carbon::createFromFormat('d/m/Y', $value)->startOfDay();
                    } catch (\Exception $e) {
                        return; // date_format above already reports malformed input
                    }

                    if ($date->lessThanOrEqualTo(Carbon::today())) {
                        $fail('The due date must be a date in the future.');
                    }
                },
            ],
            'artworkBrief' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (trim(strip_tags((string) $value)) === '') {
                        $fail('This field is required.');
                    }
                },
            ],
            'additionalComments' => 'nullable|string|max:5000',
            'artworkFiles' => 'nullable|array',
            'artworkFiles.*' => 'nullable|file|max:' . self::ARTWORK_UPLOAD_MAX_KB . '|mimes:' . self::ARTWORK_UPLOAD_MIMES,
        ];
    }

    /**
     * Friendly validation messages. Without this, Livewire/Laravel would
     * fall back to auto-generated messages built from the raw property
     * path (e.g. "The field values.1 field is required."), since these
     * fields are dynamic (keyed by custom_field_id) and have no fixed
     * attribute name to derive a nice label from.
     */
    protected function messages(): array
    {
        return [
            'selectedProductId.required' => 'This field is required.',
            'fieldValues.*.required' => 'This field is required.',
            'fieldValues.*.numeric' => 'This field must be a number.',
            'fieldValues.*.email' => 'Please enter a valid email address.',
            'fieldValues.*.regex' => 'Please enter a valid phone number.',
            'fieldOtherValues.*.required_if' => 'This field is required.',
            'artworkRequiredId.required' => 'This field is required.',
            'artworkRequiredId.exists' => 'Please choose one of the options above.',
            'artworkDueDate.required' => 'This field is required.',
            'artworkDueDate.date_format' => 'Please choose a date from the calendar.',
            'artworkFiles.*.max' => 'Each file must be smaller than 15MB.',
            'artworkFiles.*.mimes' => 'Only images, PDF, and Word documents are allowed.',
        ];
    }

    /**
     * Disabled fields are shown greyed out, so whatever the browser sent for
     * them is ignored: they always hold their default value.
     */
    private function lockDisabledFields(): void
    {
        foreach ($this->customFields as $field) {
            if ($field->is_disabled && $field->isTextLike()) {
                $this->fieldValues[$field->id] = (string) $field->default_value;
            }
        }
    }

    public function submit(StatusService $statusService, EmailService $emailService)
    {
        $this->lockDisabledFields();
        $this->validate($this->fieldRules());

        $showsArtwork = $this->showsArtwork();

        $order = DB::transaction(function () use ($statusService, $showsArtwork) {
            $order = OrderModel::create([
                'user_id' => Auth::id(),
                'product_id' => $this->selectedProductId,
                'order_number' => OrderModel::generateOrderNumber(),
                'status_id' => $statusService->new,
                'artwork_required_id' => $showsArtwork ? $this->artworkRequiredId : null,
                'artwork_due_date' => $this->artworkDueDate !== ''
                    ? Carbon::createFromFormat('d/m/Y', $this->artworkDueDate)->toDateString()
                    : null,
                'artwork_brief' => $showsArtwork ? $this->artworkBrief : null,
                'additional_comments' => $this->additionalComments ?: null,
            ]);

            $availableFieldIds = collect($this->customFields)->pluck('id')->all();

            foreach ($this->customFields as $field) {
                if (!$field->isVisibleGiven($this->fieldValues, $availableFieldIds)) {
                    // Hidden fields don't get saved, even if they still
                    // hold a leftover value from before the parent changed.
                    continue;
                }

                if ($field->type === \App\Models\CustomField::TYPE_IMAGE) {
                    // Nothing submitted by the customer for this type - the
                    // image lives on the custom field definition itself.
                    continue;
                }

                $otherValue = $field->otherOptionValue();

                if ($field->type === \App\Models\CustomField::TYPE_CHECKBOX) {
                    // Several options can be ticked at once - store them as
                    // one comma-separated value, same as a single row holds
                    // a single selection for radio/dropdown fields.
                    $selected = array_values(array_filter((array) ($this->fieldValues[$field->id] ?? [])));

                    if ($otherValue !== null && in_array($otherValue, $selected, true)) {
                        $typed = trim((string) ($this->fieldOtherValues[$field->id] ?? ''));
                        if ($typed !== '') {
                            $selected = array_map(fn ($v) => $v === $otherValue ? $typed : $v, $selected);
                        }
                    }

                    if (empty($selected)) {
                        continue;
                    }

                    OrderFieldValue::create([
                        'order_id' => $order->id,
                        'custom_field_id' => $field->id,
                        'value' => implode(', ', $selected),
                    ]);

                    continue;
                }

                $value = $this->fieldValues[$field->id] ?? null;

                if ($otherValue !== null && $value === $otherValue) {
                    $typed = trim((string) ($this->fieldOtherValues[$field->id] ?? ''));
                    if ($typed !== '') {
                        $value = $typed;
                    }
                }

                if ($value === null || $value === '') {
                    continue;
                }

                OrderFieldValue::create([
                    'order_id' => $order->id,
                    'custom_field_id' => $field->id,
                    'value' => $value,
                ]);
            }

            foreach ($showsArtwork ? $this->artworkFiles : [] as $file) {
                $path = $file->store('artwork-uploads', 'public');

                OrderArtworkFile::create([
                    'order_id' => $order->id,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }

            return $order;
        });

        $this->finishSubmit($order, $emailService);
    }

    private function finishSubmit(OrderModel $order, EmailService $emailService): void
    {
        // Notification email is logged outside the transaction, and
        // failure here is never allowed to undo an order that was
        // otherwise submitted successfully - it's only logged.
        try {
            $emailService->logOrderEmail($order, Auth::user());
        } catch (\Throwable $e) {
            Log::error('Failed to log order notification email.', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);
        }

        $this->successMessage = "Order submitted successfully.";
        $this->lastOrderNumber = $order->order_number;
        $this->selectedProductId = null;
        $this->customFields = [];
        $this->fieldValues = [];
        $this->fieldOtherValues = [];
        $this->artworkRequiredId = '';
        $this->artworkDueDate = '';
        $this->artworkBrief = '';
        $this->additionalComments = '';
        $this->artworkFiles = [];
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();

        return view('livewire.order', [
            'user' => $user,
            'pageTitle' => $this->pageTitle,
        ])
            ->layout('layouts.authenticated', [
                'user' => new UserResource(Auth::user()),
            ]);
    }
}
