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
        // Schema::table('index_values', function (Blueprint $table) {
        //     $table->date('effective_date')->nullable()->after('index_type_id');
        // });

        // // Luego en un seeder o migration:
        // DB::table('index_values')->get()->each(function ($row) {
        //     if ($row->period) {
        //         $effective = \Carbon\Carbon::createFromFormat('Y-m', $row->period)->startOfMonth()->toDateString();
        //     } else {
        //         $effective = $row->date;
        //     }

        //     DB::table('index_values')->where('id', $row->id)->update(['effective_date' => $effective]);
        // });

        // Finalmente:
        Schema::table('index_values', function (Blueprint $table) {
            // $table->dropUnique('index_values_percentage_unique');
            // $table->dropUnique('index_values_ratio_unique');
            // $table->dropColumn(['period', 'date']);
            $table->unique(['index_type_id', 'effective_date']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
