<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UsersSeeder::class,
            RolesAndPermissionsSeeder::class,

            IndexTypeSeeder::class,
            CreebbaIndexSeeder::class,
            CasaPropiaIndexSeeder::class,

            TaxConditionSeeder::class,
            DocumentTypeSeeder::class,
            PropertyTypeSeeder::class,
            CivilStatusSeeder::class,
            CountrySeeder::class,
            StateSeeder::class,
            CitySeeder::class,
            NeighborhoodsSeeder::class,
            NationalitySeeder::class,
            AttachmentCategorySeeder::class,
            DepartmentSeeder::class,
            ConfigurationSeeder::class,

            TaxRateSeeder::class,
            PaymentMethodSeeder::class,
            SalePointSeeder::class,
            VoucherTypeSeeder::class,
            ServiceTypeSeeder::class,
            BookletSeeder::class,
            CashAccountSeeder::class,
        ]);
    }
}
