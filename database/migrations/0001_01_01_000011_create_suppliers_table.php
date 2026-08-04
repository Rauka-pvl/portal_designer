<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('telegram')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('website')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('address')->nullable();
            $table->string('sphere')->nullable();
            $table->string('work_terms_type')->nullable();
            $table->string('work_terms_value')->nullable();
            $table->json('brands')->nullable();
            $table->json('cities_presence')->nullable();
            $table->text('comment')->nullable();
            $table->string('org_form')->nullable();
            $table->string('inn', 20)->nullable();
            $table->string('kpp', 20)->nullable();
            $table->string('ogrn', 20)->nullable();
            $table->string('okpo', 20)->nullable();
            $table->string('legal_address')->nullable();
            $table->string('actual_address')->nullable();
            $table->boolean('address_match')->default(false);
            $table->string('director')->nullable();
            $table->string('accountant')->nullable();
            $table->string('bik', 20)->nullable();
            $table->string('bank')->nullable();
            $table->string('checking_account')->nullable();
            $table->string('corr_account')->nullable();
            $table->text('comment_bank')->nullable();
            $table->boolean('recommend')->default(false);
            $table->string('profile_status', 50)->default('draft');
            $table->string('account_status', 50)->default('active');
            $table->integer('guarantee_balance')->default(0);
            $table->timestamp('deposit_activated_at')->nullable();
            $table->text('temporary_password_encrypted')->nullable();
            $table->string('temporary_password')->nullable();
            $table->string('bin', 20)->nullable();
            $table->string('company_name')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('warehouse_address')->nullable();
            $table->decimal('guarantee_deposit', 12, 2)->default(0);
            $table->enum('moderation_status', ['pending', 'approved', 'rejected', 'draft'])->default('pending');
            $table->text('moderation_reason')->nullable();
            $table->text('moderation_comment')->nullable();
            $table->foreignId('moderation_reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderation_reviewed_at')->nullable();
            $table->boolean('is_confirmed_by_designer')->default(false);
            $table->boolean('is_referral_submitted')->default(false);
            $table->boolean('is_referral')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_by_user_id');
            $table->index('city');
            $table->index('moderation_status');
            $table->index('profile_status');
            $table->index('account_status');
            $table->index('is_active');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
