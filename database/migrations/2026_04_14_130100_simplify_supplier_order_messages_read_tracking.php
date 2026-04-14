<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplier_order_messages')) {
            Schema::table('supplier_order_messages', function (Blueprint $table) {
                if (! Schema::hasColumn('supplier_order_messages', 'read_by_designer_at')) {
                    $table->timestamp('read_by_designer_at')->nullable()->after('message');
                }
                if (! Schema::hasColumn('supplier_order_messages', 'read_by_supplier_at')) {
                    $table->timestamp('read_by_supplier_at')->nullable()->after('read_by_designer_at');
                }
            });
        }

        if (Schema::hasTable('supplier_order_message_reads') && Schema::hasTable('supplier_order_messages')) {
            $rows = DB::table('supplier_order_message_reads as r')
                ->join('supplier_orders as o', 'o.id', '=', 'r.supplier_order_id')
                ->select([
                    'r.supplier_order_id',
                    'r.user_id',
                    'r.last_read_message_id',
                    'r.updated_at',
                    'o.user_id as designer_user_id',
                ])
                ->orderBy('r.id')
                ->get();

            foreach ($rows as $row) {
                $lastReadMessageId = (int) ($row->last_read_message_id ?? 0);
                $readAt = $row->updated_at ?? now();

                if ($lastReadMessageId < 1) {
                    continue;
                }

                $column = (int) $row->user_id === (int) $row->designer_user_id
                    ? 'read_by_designer_at'
                    : 'read_by_supplier_at';

                DB::table('supplier_order_messages')
                    ->where('supplier_order_id', (int) $row->supplier_order_id)
                    ->where('id', '<=', $lastReadMessageId)
                    ->whereNull($column)
                    ->update([$column => $readAt]);
            }

            Schema::dropIfExists('supplier_order_message_reads');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('supplier_order_message_reads')) {
            Schema::create('supplier_order_message_reads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_order_id')->constrained('supplier_orders')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('last_read_message_id')->nullable()->constrained('supplier_order_messages')->nullOnDelete();
                $table->timestamp('updated_at')->nullable();

                $table->unique(['supplier_order_id', 'user_id']);
            });
        }
    }
};
