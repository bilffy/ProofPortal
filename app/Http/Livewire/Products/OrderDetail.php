<?php

namespace App\Http\Livewire\Products;

use App\Http\Resources\UserResource;
use App\Models\Email;
use App\Models\Order;
use App\Services\Proofing\EmailBodyExtractor;
use Auth;
use Livewire\Component;

class OrderDetail extends Component
{
    public Order $order;

    /** Which order email the preview modal is showing: 'print', 'requestor' or null (closed). */
    public ?string $viewingEmail = null;

    public function mount(Order $order)
    {
        if (!Auth::user()->isSuperAdmin()) {
            return redirect()->route('dashboard');
        }

        $this->order = $order->load([
            'user',
            'user.franchises',
            'user.schools',
            'product',
            'status',
            'fieldValues',
            'artworkFiles',
            'artworkRequired',
        ]);
    }

    public function viewEmail(string $recipient): void
    {
        if (in_array($recipient, [Email::ORDER_RECIPIENT_PRINT, Email::ORDER_RECIPIENT_REQUESTOR], true)) {
            $this->viewingEmail = $recipient;
        }
    }

    public function closeEmail(): void
    {
        $this->viewingEmail = null;
    }

    /**
     * The order's print-team and requestor emails, keyed by recipient. A key
     * is null when that email was never raised (e.g. the order predates email
     * logging, or PRINT_REQUEST_EMAIL was unset at the time).
     *
     * @return array{print: ?Email, requestor: ?Email}
     */
    public function getOrderEmailsProperty(): array
    {
        $emails = Email::with(['status', 'template'])
            ->where('order_id', $this->order->id)
            ->orderByDesc('id')
            ->get()
            ->unique('order_recipient')
            ->keyBy('order_recipient');

        return [
            Email::ORDER_RECIPIENT_PRINT => $emails->get(Email::ORDER_RECIPIENT_PRINT),
            Email::ORDER_RECIPIENT_REQUESTOR => $emails->get(Email::ORDER_RECIPIENT_REQUESTOR),
        ];
    }

    /**
     * The email currently open in the preview modal, with its body already
     * extracted for the iframe.
     *
     * @return ?array{email: Email, html: string}
     */
    public function getViewedEmailProperty(): ?array
    {
        $email = $this->viewingEmail ? ($this->orderEmails[$this->viewingEmail] ?? null) : null;

        if (!$email) {
            return null;
        }

        return [
            'email' => $email,
            'html' => app(EmailBodyExtractor::class)->extract((string) $email->email_content),
        ];
    }

    /**
     * Field + submitted value pairs, in the product's assigned field order
     * (product_custom_field.sort_order) rather than however
     * order_field_values rows happen to be stored.
     *
     * @return array<int, array{field: \App\Models\CustomField, value: ?string}>
     */
    public function getFieldRowsProperty(): array
    {
        if (!$this->order->product) {
            return [];
        }

        $fields = $this->order->product->customFields()->get();

        // Image-type fields are reference material shown to the customer
        // while they fill in the order (e.g. "here's what this looks
        // like"), not something the customer submits - there's no
        // submitted value to show here, so skip the whole row for them.
        return $fields->reject(fn ($field) => $field->type === \App\Models\CustomField::TYPE_IMAGE)
            ->map(function ($field) {
                $fieldValue = $this->order->fieldValues->firstWhere('custom_field_id', $field->id);

                return [
                    'field' => $field,
                    'value' => $fieldValue?->value,
                ];
            })->values()->all();
    }

    /**
     * Franchise / contact / school details for the ordering user, sourced
     * from existing relations rather than order-specific input.
     *
     * @return array{franchise_name: ?string, contact_name: ?string, contact_number: ?string, email: ?string, school_name: ?string}
     */
    public function getContactDetailsProperty(): array
    {
        $user = $this->order->user;

        if (!$user) {
            return [
                'franchise_name' => null,
                'contact_name' => null,
                'contact_number' => null,
                'email' => null,
                'school_name' => null,
            ];
        }

        $contactName = trim(($user->firstname ?? '') . ' ' . ($user->lastname ?? ''));

        return [
            'franchise_name' => $user->getFranchise()?->name,
            'contact_name' => $contactName !== '' ? $contactName : ($user->name ?? null),
            'contact_number' => $user->contact ?? null,
            'email' => $user->email ?? null,
            'school_name' => $user->getSchool()?->name,
        ];
    }

    public function render()
    {
        return view('livewire.products.order-detail')
            ->layout('layouts.authenticated', [
                'user' => new UserResource(Auth::user()),
            ]);
    }
}
