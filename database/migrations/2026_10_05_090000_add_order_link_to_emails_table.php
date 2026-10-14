<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            // Ties the two emails raised for an order (the print team's copy
            // and the requestor's copy) back to it, so the admin order page
            // can show each one and its delivery status. order_recipient is
            // 'print' or 'requestor'.
            $table->integer('school_id')->nullable()->after('ts_jobkey');
            $table->unsignedBigInteger('order_id')->nullable()->after('template_id');
            $table->string('order_recipient', 20)->nullable()->after('order_id');
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
        });

        $this->backfillExistingOrders();
    }

    /**
     * Best-effort link for orders placed before this column existed.
     *
     * Rows are matched on the "Ordering" template, the submitting user, and
     * the order number appearing in the Subject header of the stored EML
     * (headers are not encoded for plain ASCII subjects). The copy addressed
     * to the submitter is the requestor email; the other is the print team's.
     * Anything that can't be matched confidently is left unlinked.
     */
    protected function backfillExistingOrders(): void
    {
        $templateId = DB::table('templates')->where('template_name', 'Ordering')->value('id');

        if (!$templateId) {
            return;
        }

        $orders = DB::table('orders')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->whereNotNull('orders.order_number')
            ->get(['orders.id', 'orders.order_number', 'orders.user_id', 'users.email as user_email']);

        foreach ($orders as $order) {
            $emails = DB::table('emails')
                ->where('template_id', $templateId)
                ->where('generated_from_user_id', $order->user_id)
                ->whereNull('order_id')
                ->where('email_content', 'like', '%' . $order->order_number . '%')
                ->get(['id', 'email_to', 'email_content']);

            foreach ($emails as $email) {
                // Exact order number only (ORD-2026-0001 must not claim ORD-2026-00012).
                $pattern = '/Subject:[^\r\n]*' . preg_quote($order->order_number, '/') . '(?![0-9])/i';
                if (!preg_match($pattern, (string) $email->email_content)) {
                    continue;
                }

                $isRequestor = strcasecmp(trim((string) $email->email_to), trim((string) $order->user_email)) === 0;

                DB::table('emails')->where('id', $email->id)->update([
                    'order_id' => $order->id,
                    'order_recipient' => $isRequestor ? 'requestor' : 'print',
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropColumn(['order_id', 'order_recipient']);
        });
    }
};
