<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Prefer codes already used in location_requests / UI.
        $canonical = [
            // Sale (S)
            ['TOP_MID_WALL', 'Shelf_Middle shelve & Wall shelve', 'sale', 1, true],
            ['SHELF_MIDDLE SHELVE & WALL SHELVE', 'Shelf_Middle shelve & Wall shelve', 'sale', 2, true],
            ['TOP_STOCK', 'Top stock', 'sale', 3, true],
            ['GONDOLAR & POS', 'Gondolar & POS', 'sale', 4, true],
            ['STRUCTURE_DISPLAYS', 'Structure-Displays', 'sale', 5, true],
            ['SANITARY_WARE', 'Sanitary ware-Displays', 'sale', 6, true],
            ['GARDEN_ACCESSORIES', 'Garden-Displays', 'sale', 7, true],
            ['HARDWARE_TOOLS', 'Hardware & Tools Displays', 'sale', 8, true],
            ['SURFACE_COVERING', 'Surface Cover- Displays', 'sale', 9, true],
            ['DOOR_WINDOW', 'Door & Window Displays', 'sale', 10, true],
            ['ELECTRICAL_ACCESSORIES', 'Electrical Displays', 'sale', 11, true],
            ['HOME_APPLIANCE', 'Home App-Displays', 'sale', 12, true],
            ['PAINT_CHEMICAL', 'Paint & Chemical Displays', 'sale', 13, true],
            ['HOUSEWARE_KITCHEN', 'Houseware & Kitchen Displays', 'sale', 14, true],
            ['FURNITURE_BEDDING', 'Furniture & Bedding', 'sale', 15, true],
            ['STATIONARY_DIGITAL', 'Stationary & Digital Displays', 'sale', 16, true],
            ['OUTSIDE_STORE', 'Outside store Displays', 'sale', 17, true],
            ['PROMOTION_ZONE_GF', 'Promotion zone Ground floor', 'sale', 18, true],
            ['PROMOTION ZONE,FIRST FLOOR', 'Promotion zone First Floor', 'sale', 19, true],
            ['R011(DAMAGE)', 'R011 (Damage)', 'sale', 20, true],
            ['RG', 'RG', 'sale', 21, true],
            ['OFFICE USE-MARKETING', 'Office use (Marketing)', 'sale', 22, true],
            ['OFFICE USE-ADMIN/HR', 'Office use (Admin/HR)', 'sale', 23, true],
            ['OFFICE USE-IT', 'Office use (IT)', 'sale', 24, true],
            ['OFFICE USE-LP', 'Office use (LP)', 'sale', 25, true],
            ['OFFICE USE-ACCOUNT', 'Office use (Account)', 'sale', 26, true],
            ['OFFICE USE-SERVICE TEAM', 'Office use (Service team)', 'sale', 27, true],
            ['OFFICE USE-OTHER', 'Office use (Other)', 'sale', 28, true],

            // Warehouse (W)
            ['WAREHOUSE GROUND FLOOR', 'Warehouse Ground floor', 'warehouse', 29, true],
            ['WAREHOUSE FIRST FLOOR', 'Warehouse First Floor', 'warehouse', 30, true],
            ['WAREHOUSE CEMENT', 'Warehouse Cement', 'warehouse', 31, true],
            ['WAREHOUSE STEEL(BESIDE)', 'Warehouse Steel (Beside)', 'warehouse', 32, true],
            ['WAREHOUSE CERAMIC(BESIDE)', 'Warehouse Ceramic (Beside)', 'warehouse', 33, true],
            ['WHMGLD', 'Warehouse', 'warehouse', 34, true],

            // Legacy — keep for old requests, hide from pickers
            ['RG_WAREHOUSE', 'RG Warehouse (legacy)', 'warehouse', 99, false],
        ];

        foreach ($canonical as [$code, $name, $category, $sortOrder, $isActive]) {
            $existing = DB::table('location_types')->where('code', $code)->first();

            if ($existing) {
                DB::table('location_types')->where('code', $code)->update([
                    'name' => $name,
                    'category' => $category,
                    'is_active' => $isActive,
                    'sort_order' => $sortOrder,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('location_types')->insert([
                    'code' => $code,
                    'name' => $name,
                    'category' => $category,
                    'is_active' => $isActive,
                    'sort_order' => $sortOrder,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Deactivate unused duplicate codes introduced by the previous sync migration.
        $duplicateCodes = [
            'GONDOLAR_POS',
            'PROMOTION_ZONE_FF',
            'R011_DAMAGE',
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

        DB::table('location_types')
            ->whereIn('code', $duplicateCodes)
            ->update([
                'is_active' => false,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        // No destructive rollback — previous sync migration remains the source of truth.
    }
};
