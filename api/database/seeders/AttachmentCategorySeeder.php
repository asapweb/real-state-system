<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AttachmentCategory;

class AttachmentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'DNI', 'context' => 'client'],
            ['name' => 'Pasaporte', 'context' => 'client'],
            ['name' => 'Factura de luz', 'context' => 'client'],
            ['name' => 'Plano PH', 'context' => 'property'],
            ['name' => 'Escritura', 'context' => 'property'],
            ['name' => 'Reglamento de copropiedad', 'context' => 'property'],
            ['name' => 'Contrato', 'context' => 'contract'],
            ['name' => 'Recibo de pago', 'context' => 'contract'],
        ];

        foreach ($categories as $cat) {
            AttachmentCategory::updateOrCreate([
                'name' => $cat['name'],
                'context' => $cat['context'],
            ]);
        }
    }
}
