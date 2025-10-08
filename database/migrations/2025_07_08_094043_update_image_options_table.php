<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use function Livewire\after;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('image_options', function (Blueprint $table) {
            $table->dropColumn(['dimensions_width']);
            $table->dropColumn(['dimensions_height']);
            $table->dropColumn(['dpi']);
            
        });
        
        Schema::table('image_options', function (Blueprint $table) {
            $table->integer('long_edge')->nullable()->after('file_format');
            $table->enum('image_use', ['print', 'screen'])->nullable()->after('long_edge');;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('image_options', function (Blueprint $table) {
            $table->dropColumn('long_edge');
            $table->dropColumn('image_use');
        });
    }
};
