<?php

namespace Database\Seeders;

use App\Models\Segment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SegmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Segment::insert([
            ['name' => 'Restaurantes'],
            ['name' => 'Tiendas de ropa'],
            ['name' => 'Supermercados'],
            ['name' => 'Belleza y cuidado personal'],
            ['name' => 'Belleza y cuidado personal'],
            ['name' => 'Ocio y entretenimiento'],
            ['name' => 'Viajes y alojamiento'],
            ['name' => 'Tecnología y electrónica'],
            ['name' => 'Deportes y fitness'],
            ['name' => 'Hogar y jardín'],
            ['name' => 'Educación y formación'],
            ['name' => 'Salud y bienestar'],
            ['name' => 'Arte y cultura'],
            ['name' => 'Servicios financieros'],
            ['name' => 'Alimentos y bebidas'],
            ['name' => 'Moda y accesorios'],
            ['name' => 'Productos de belleza'],
            ['name' => 'Entretenimiento y eventos'],
            ['name' => 'Hospedaje y alojamiento vacacional'],
            ['name' => 'Actividades al aire libre'],
            ['name' => 'Reparaciones de automóviles'],
            ['name' => 'Decoración del hogar'],
            ['name' => 'Cursos en línea y tutoriales'],
            ['name' => 'Terapias alternativas y bienestar'],
            ['name' => 'Artículos para mascotas y cuidado animal'],
            ['name' => 'Galerías de arte y exposiciones'],
            ['name' => 'Asesoramiento financiero y seguros'],
            ['name' => 'Juguetes y juegos'],
            ['name' => 'otro']
         ]);
    }
}
