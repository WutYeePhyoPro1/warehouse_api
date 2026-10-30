<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $types = [
            // Sale (S)
            ['TOP_MID_WALL', 'Shelf_Middle shelve & Wall shelve', 'sale', 1],
            ['TOP_STOCK', 'Top stock', 'sale', 2],
            ['GONDOLAR_POS', 'Gondolar & POS', 'sale', 3],
            ['STRUCTURE_DISPLAYS', 'Structure-Displays', 'sale', 4],
            ['SANITARY_WARE', 'Sanitary ware-Displays', 'sale', 5],
            ['GARDEN_ACCESSORIES', 'Garden-Displays', 'sale', 6],
            ['HARDWARE_TOOLS', 'Hardware & Tools Displays', 'sale', 7],
            ['SURFACE_COVERING', 'Surface Cover- Displays', 'sale', 8],
            ['DOOR_WINDOW', 'Door & Window Displays', 'sale', 9],
            ['ELECTRICAL_ACCESSORIES', 'Electrical Displays', 'sale', 10],
            ['HOME_APPLIANCE', 'Home App-Displays', 'sale', 11],
            ['PAINT_CHEMICAL', 'Paint & Chemical Displays', 'sale', 12],
            ['HOUSEWARE_KITCHEN', 'Houseware & Kitchen Displays', 'sale', 13],
            ['FURNITURE_BEDDING', 'Furniture & Bedding', 'sale', 14],
            ['STATIONARY_DIGITAL', 'Stationary & Digital Displays', 'sale', 15],
            ['OUTSIDE_STORE', 'Outside store Displays', 'sale', 16],
            ['PROMOTION_ZONE_GF', 'Promotion zone Ground floor', 'sale', 17],
            ['PROMOTION_ZONE_FF', 'Promotion zone First Floor', 'sale', 18],
            ['R011_DAMAGE', 'R011 (Damage)', 'sale', 19],
            ['RG', 'RG', 'sale', 20],
            ['OFFICE_MARKETING', 'Office use (Marketing)', 'sale', 21],
            ['OFFICE_ADMIN_HR', 'Office use (Admin/HR)', 'sale', 22],
            ['OFFICE_IT', 'Office use (IT)', 'sale', 23],
            ['OFFICE_LP', 'Office use (LP)', 'sale', 24],
            ['OFFICE_ACCOUNT', 'Office use (Account)', 'sale', 25],
            ['OFFICE_SERVICE', 'Office use (Service team)', 'sale', 26],
            ['OFFICE_OTHER', 'Office use (Other)', 'sale', 27],

            // Warehouse (W)
            ['WH_GROUND', 'Warehouse Ground floor', 'warehouse', 28],
            ['WH_FIRST', 'Warehouse First Floor', 'warehouse', 29],
            ['WH_CEMENT', 'Warehouse Cement', 'warehouse', 30],
            ['WH_STEEL', 'Warehouse Steel (Beside)', 'warehouse', 31],
            ['WH_CERAMIC', 'Warehouse Ceramic (Beside)', 'warehouse', 32],
        ];

        foreach ($types as [$code, $name, $category, $sortOrder]) {
            $existing = DB::table('location_types')->where('code', $code)->first();

            if ($existing) {
                DB::table('location_types')->where('code', $code)->update([
                    'name' => $name,
                    'category' => $category,
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                    'updated_at' => $now,
                ]);
            } else {
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

        // Old single warehouse type — keep for history, but deactivate.
        DB::table('location_types')
            ->where('code', 'RG_WAREHOUSE')
            ->update([
                'is_active' => false,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        $codesToRemove = [
            'TOP_STOCK',
            'GONDOLAR_POS',
            'PROMOTION_ZONE_FF',
            'R011_DAMAGE',
            'RG',
            'OFFICE_MARKETING',
            'OFFICE_ADMIN_HR',
            'OFFICE_IT',
            'OFFICE_LP',
            'OFFICE_ACCOUNT',
            'OFFICE_SERVICE',
            'OFFICE_OTHER',
            'WH_GROUND',
            'WH_FIRST',
            'WH_CEMENT',
            'WH_STEEL',
            'WH_CERAMIC',
        ];

        DB::table('location_types')->whereIn('code', $codesToRemove)->delete();

        DB::table('location_types')
            ->where('code', 'RG_WAREHOUSE')
            ->update([
                'is_active' => true,
                'category' => 'warehouse',
                'updated_at' => now(),
            ]);
    }
};
