<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category')->default('sale'); // sale | warehouse  =>  S | W
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        // Codes match production / location coding sheet. category sale => S, warehouse => W.
        $defaults = [
            ['TOP_MID_WALL', 'Shelf_Middle shelve & Wall shelve', 'sale', 1],
            ['SHELF_MIDDLE SHELVE & WALL SHELVE', 'Shelf_Middle shelve & Wall shelve', 'sale', 2],
            ['TOP_STOCK', 'Top stock', 'sale', 3],
            ['GONDOLAR & POS', 'Gondolar & POS', 'sale', 4],
            ['STRUCTURE_DISPLAYS', 'Structure-Displays', 'sale', 5],
            ['SANITARY_WARE', 'Sanitary ware-Displays', 'sale', 6],
            ['GARDEN_ACCESSORIES', 'Garden-Displays', 'sale', 7],
            ['HARDWARE_TOOLS', 'Hardware & Tools Displays', 'sale', 8],
            ['SURFACE_COVERING', 'Surface Cover- Displays', 'sale', 9],
            ['DOOR_WINDOW', 'Door & Window Displays', 'sale', 10],
            ['ELECTRICAL_ACCESSORIES', 'Electrical Displays', 'sale', 11],
            ['HOME_APPLIANCE', 'Home App-Displays', 'sale', 12],
            ['PAINT_CHEMICAL', 'Paint & Chemical Displays', 'sale', 13],
            ['HOUSEWARE_KITCHEN', 'Houseware & Kitchen Displays', 'sale', 14],
            ['FURNITURE_BEDDING', 'Furniture & Bedding', 'sale', 15],
            ['STATIONARY_DIGITAL', 'Stationary & Digital Displays', 'sale', 16],
            ['OUTSIDE_STORE', 'Outside store Displays', 'sale', 17],
            ['PROMOTION_ZONE_GF', 'Promotion zone Ground floor', 'sale', 18],
            ['PROMOTION ZONE,FIRST FLOOR', 'Promotion zone First Floor', 'sale', 19],
            ['R011(DAMAGE)', 'R011 (Damage)', 'sale', 20],
            ['RG', 'RG', 'sale', 21],
            ['OFFICE USE-MARKETING', 'Office use (Marketing)', 'sale', 22],
            ['OFFICE USE-ADMIN/HR', 'Office use (Admin/HR)', 'sale', 23],
            ['OFFICE USE-IT', 'Office use (IT)', 'sale', 24],
            ['OFFICE USE-LP', 'Office use (LP)', 'sale', 25],
            ['OFFICE USE-ACCOUNT', 'Office use (Account)', 'sale', 26],
            ['OFFICE USE-SERVICE TEAM', 'Office use (Service team)', 'sale', 27],
            ['OFFICE USE-OTHER', 'Office use (Other)', 'sale', 28],
            ['WAREHOUSE GROUND FLOOR', 'Warehouse Ground floor', 'warehouse', 29],
            ['WAREHOUSE FIRST FLOOR', 'Warehouse First Floor', 'warehouse', 30],
            ['WAREHOUSE CEMENT', 'Warehouse Cement', 'warehouse', 31],
            ['WAREHOUSE STEEL(BESIDE)', 'Warehouse Steel (Beside)', 'warehouse', 32],
            ['WAREHOUSE CERAMIC(BESIDE)', 'Warehouse Ceramic (Beside)', 'warehouse', 33],
            ['WHMGLD', 'Warehouse', 'warehouse', 34],
        ];

        foreach ($defaults as [$code, $name, $category, $sortOrder]) {
            DB::table('location_types')->insert([
                'code' => $code,
                'name' => $name,
                'category' => $category,
                'is_active' => true,
                'sort_order' => $sortOrder,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('location_types');
    }
};
