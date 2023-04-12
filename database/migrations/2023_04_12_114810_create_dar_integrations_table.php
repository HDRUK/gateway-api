<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dar_integrations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->boolean('enabled')->default(1);
            $table->mediumText('notification_email');
            $table->char('outbound_auth_type', 45);
            $table->string('outbound_auth_key');
            $table->string('outbound_endpoints_base_url');
            $table->string('outbound_endpoints_enquiry');
            $table->string('outbound_endpoints_5safes');
            $table->string('outbound_endpoints_5safes_files');
            $table->string('inbound_service_account_id');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dar_integrations');
    }
};
