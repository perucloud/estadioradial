<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Location;
use App\Models\Post;
use App\Models\Program;
use App\Models\Schedule;
use App\Models\Stream;
use Illuminate\Database\Seeder;

class PortalSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(LocationSeeder::class);

        $categories = collect([
            ['name' => 'Regionales', 'slug' => 'regionales', 'color' => '#a61b1b', 'display_order' => 10, 'relevance_weight' => 100],
            ['name' => 'Locales', 'slug' => 'locales', 'color' => '#c4312f', 'display_order' => 20, 'relevance_weight' => 95],
            ['name' => 'Política', 'slug' => 'politica', 'color' => '#d91f18', 'display_order' => 30, 'relevance_weight' => 90],
            ['name' => 'Economía', 'slug' => 'economia', 'color' => '#9a6a13', 'display_order' => 40, 'relevance_weight' => 85],
            ['name' => 'Nacional', 'slug' => 'nacional', 'color' => '#df4b20', 'display_order' => 50, 'relevance_weight' => 80],
            ['name' => 'Cultura', 'slug' => 'cultura', 'color' => '#7837a8', 'display_order' => 60, 'relevance_weight' => 70],
            ['name' => 'Deportes', 'slug' => 'deportes', 'color' => '#18744f', 'display_order' => 70, 'relevance_weight' => 65],
            ['name' => 'Educación', 'slug' => 'educacion', 'color' => '#6046ad', 'display_order' => 80, 'relevance_weight' => 60],
            ['name' => 'Salud', 'slug' => 'salud', 'color' => '#08727d', 'display_order' => 90, 'relevance_weight' => 55],
            ['name' => 'Tecnología', 'slug' => 'tecnologia', 'color' => '#235d98', 'display_order' => 100, 'relevance_weight' => 50],
            ['name' => 'Actualidad', 'slug' => 'actualidad', 'color' => '#e5261f', 'display_order' => 110, 'relevance_weight' => 45],
        ])->mapWithKeys(function (array $data) {
            $category = Category::query()->updateOrCreate(
                ['slug' => $data['slug']],
                $data + [
                    'is_active' => true,
                    'show_in_menu' => true,
                    'show_on_home' => true,
                    'homepage_limit' => 4,
                    'homepage_layout' => 'standard',
                ],
            );

            return [$data['slug'] => $category];
        });
        $locations = Location::query()->get()->keyBy(
            fn (Location $location) => "{$location->type}:{$location->slug}"
        );

        $posts = [
            [
                'category' => 'actualidad',
                'title' => 'La radio abre una nueva ventana para conectar con todo el país',
                'slug' => 'la-radio-abre-una-nueva-ventana-para-conectar-con-todo-el-pais',
                'excerpt' => 'Iniciamos una etapa digital con noticias, cultura y programación en vivo desde una sola plataforma.',
                'body' => '<p>La radio sigue transformándose para acompañar a su audiencia dondequiera que se encuentre.</p><p>Este nuevo portal reunirá información de actualidad, programas, horarios y transmisiones en vivo con una experiencia clara para computadoras y teléfonos.</p>',
                'image' => '/images/demo/hero-radio.svg',
                'author' => 'Redacción Estación Radial',
                'featured' => true,
                'minutes_ago' => 12,
                'views_count' => 1840,
            ],
            [
                'category' => 'regionales',
                'location' => 'region:moquegua',
                'title' => 'Autoridades regionales presentan agenda de trabajo para el segundo semestre',
                'slug' => 'autoridades-regionales-presentan-agenda-de-trabajo',
                'excerpt' => 'La propuesta prioriza servicios públicos, conectividad y proyectos de alcance regional.',
                'body' => '<p>Representantes regionales presentaron una agenda conjunta para coordinar los proyectos prioritarios del segundo semestre.</p><p>Las mesas técnicas continuarán durante las próximas semanas.</p>',
                'image' => '/images/demo/news-government.svg',
                'author' => 'Mesa de noticias',
                'featured' => true,
                'minutes_ago' => 35,
                'views_count' => 3260,
            ],
            [
                'category' => 'nacional',
                'title' => 'Nuevas rutas mejoran la conectividad entre ciudades del interior',
                'slug' => 'nuevas-rutas-mejoran-la-conectividad-entre-ciudades',
                'excerpt' => 'Los nuevos servicios buscan reducir tiempos de viaje y dinamizar el comercio local.',
                'body' => '<p>La ampliación de rutas permitirá una conexión más frecuente entre capitales provinciales y comunidades cercanas.</p>',
                'image' => '/images/demo/news-transport.svg',
                'author' => 'Redacción Nacional',
                'featured' => true,
                'minutes_ago' => 58,
                'views_count' => 2150,
            ],
            [
                'category' => 'cultura',
                'title' => 'Festival reúne música, memoria y tradiciones de distintas regiones',
                'slug' => 'festival-reune-musica-memoria-y-tradiciones',
                'excerpt' => 'Artistas y colectivos culturales compartirán presentaciones durante todo el fin de semana.',
                'body' => '<p>El encuentro cultural contará con música en vivo, narración oral, danza y una feria de productores independientes.</p>',
                'image' => '/images/demo/news-culture.svg',
                'author' => 'Redacción Cultural',
                'featured' => false,
                'minutes_ago' => 84,
                'views_count' => 4810,
            ],
            [
                'category' => 'deportes',
                'title' => 'Clubes locales se preparan para una nueva jornada deportiva',
                'slug' => 'clubes-locales-se-preparan-para-una-nueva-jornada',
                'excerpt' => 'Los planteles completaron sus entrenamientos y confirmaron sus convocatorias.',
                'body' => '<p>La fecha deportiva comenzará el sábado y tendrá encuentros en diferentes escenarios de la región.</p>',
                'image' => '/images/demo/news-sports.svg',
                'author' => 'Redacción Deportes',
                'featured' => false,
                'minutes_ago' => 120,
                'views_count' => 3920,
            ],
            [
                'category' => 'actualidad',
                'title' => 'Campaña ciudadana promueve el cuidado responsable del agua',
                'slug' => 'campana-ciudadana-promueve-el-cuidado-responsable-del-agua',
                'excerpt' => 'Instituciones y voluntarios desarrollarán actividades informativas en barrios y colegios.',
                'body' => '<p>La iniciativa busca compartir medidas sencillas para usar el agua de manera responsable en el hogar.</p>',
                'image' => '/images/demo/news-community.svg',
                'author' => 'Mesa de noticias',
                'featured' => false,
                'minutes_ago' => 175,
                'views_count' => 2870,
            ],
            [
                'category' => 'economia',
                'title' => 'Mercados regionales impulsan nuevas oportunidades para pequeños productores',
                'slug' => 'mercados-regionales-impulsan-oportunidades-para-productores',
                'excerpt' => 'Ferias comerciales y alianzas locales facilitan el acceso de emprendimientos a nuevos consumidores.',
                'body' => '<p>Productores de distintas provincias participan en ruedas comerciales y espacios de capacitación para fortalecer sus negocios.</p>',
                'image' => '/images/demo/news-economy.svg',
                'author' => 'Redacción Economía',
                'featured' => false,
                'minutes_ago' => 220,
                'views_count' => 5570,
            ],
            [
                'category' => 'salud',
                'title' => 'Jornada preventiva acerca servicios de salud a familias de zonas rurales',
                'slug' => 'jornada-preventiva-acerca-servicios-de-salud-a-familias',
                'excerpt' => 'Equipos itinerantes brindarán orientación, controles básicos y atención especializada.',
                'body' => '<p>La jornada incluye evaluaciones preventivas y actividades educativas dirigidas a personas de todas las edades.</p>',
                'image' => '/images/demo/news-health.svg',
                'author' => 'Redacción Salud',
                'featured' => false,
                'minutes_ago' => 250,
                'views_count' => 4450,
            ],
            [
                'category' => 'tecnologia',
                'title' => 'Radios locales incorporan herramientas digitales para ampliar su cobertura',
                'slug' => 'radios-locales-incorporan-herramientas-digitales',
                'excerpt' => 'Nuevas plataformas permiten distribuir contenidos en vivo y fortalecer la participación de la audiencia.',
                'body' => '<p>La transformación digital abre nuevas posibilidades para que las emisoras locales lleguen a oyentes dentro y fuera de sus regiones.</p>',
                'image' => '/images/demo/news-technology.svg',
                'author' => 'Redacción Tecnología',
                'featured' => false,
                'minutes_ago' => 290,
                'views_count' => 3640,
            ],
            [
                'category' => 'educacion',
                'title' => 'Bibliotecas escolares renuevan sus espacios para promover la lectura',
                'slug' => 'bibliotecas-escolares-renuevan-espacios-para-promover-la-lectura',
                'excerpt' => 'Estudiantes y docentes participan en clubes de lectura y actividades de creación literaria.',
                'body' => '<p>Los nuevos espacios buscan convertir la lectura en una experiencia cercana, participativa y cotidiana.</p>',
                'image' => '/images/demo/news-education.svg',
                'author' => 'Redacción Educación',
                'featured' => false,
                'minutes_ago' => 340,
                'views_count' => 3010,
            ],
        ];

        foreach ($posts as $item) {
            Post::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'category_id' => $categories[$item['category']]->id,
                    'location_id' => isset($item['location'])
                        ? $locations->get($item['location'])?->id
                        : null,
                    'title' => $item['title'],
                    'excerpt' => $item['excerpt'],
                    'body' => $item['body'],
                    'image' => $item['image'],
                    'author' => $item['author'],
                    'status' => 'published',
                    'is_featured' => $item['featured'],
                    'views_count' => $item['views_count'],
                    'published_at' => now()->subMinutes($item['minutes_ago']),
                ],
            );
        }

        $programs = collect([
            [
                'title' => 'Primera Edición',
                'slug' => 'primera-edicion',
                'summary' => 'Noticias para comenzar el día bien informado.',
                'description' => 'Resumen informativo, entrevistas y enlaces con nuestros corresponsales.',
                'hosts' => 'Equipo de prensa',
                'image' => '/images/demo/program-news.svg',
            ],
            [
                'title' => 'Voces de nuestra tierra',
                'slug' => 'voces-de-nuestra-tierra',
                'summary' => 'Música, historias y protagonistas de todas las regiones.',
                'description' => 'Un encuentro diario con la diversidad cultural y musical del país.',
                'hosts' => 'María Torres',
                'image' => '/images/demo/program-culture.svg',
            ],
            [
                'title' => 'La hora deportiva',
                'slug' => 'la-hora-deportiva',
                'summary' => 'Resultados, análisis y conversación deportiva.',
                'description' => 'Toda la actualidad del deporte nacional e internacional.',
                'hosts' => 'Carlos Mendoza',
                'image' => '/images/demo/program-sports.svg',
            ],
            [
                'title' => 'Noches de vinilo',
                'slug' => 'noches-de-vinilo',
                'summary' => 'Una selección musical para cerrar la jornada.',
                'description' => 'Clásicos, nuevas voces y las historias que viven detrás de cada canción.',
                'hosts' => 'Lucía Reyes',
                'image' => '/images/demo/program-music.svg',
            ],
        ])->map(function (array $data, int $index) {
            return Program::query()->updateOrCreate(
                ['slug' => $data['slug']],
                $data + ['is_active' => true, 'display_order' => ($index + 1) * 10],
            );
        });

        $scheduleRows = [];

        foreach (range(1, 7) as $day) {
            $slots = [
                ['program' => $programs[0], 'start' => '06:00', 'end' => '09:00'],
                ['program' => $programs[1], 'start' => '09:00', 'end' => '12:00'],
                ['program' => $programs[2], 'start' => '18:00', 'end' => '20:00'],
                ['program' => $programs[3], 'start' => '20:00', 'end' => '23:00'],
            ];

            foreach ($slots as $slot) {
                $scheduleRows[] = [
                    'program_id' => $slot['program']->id,
                    'day_of_week' => $day,
                    'starts_at' => $slot['start'],
                    'ends_at' => $slot['end'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        Schedule::query()->upsert(
            $scheduleRows,
            ['program_id', 'day_of_week', 'starts_at'],
            ['ends_at', 'updated_at'],
        );

        Stream::query()->updateOrCreate(
            ['type' => 'audio', 'name' => 'Señal principal'],
            [
                'format' => 'mp3',
                'url' => null,
                'cover' => '/images/demo/stream-cover.svg',
                'is_active' => true,
                'is_primary' => true,
                'sort_order' => 1,
            ],
        );

        Stream::query()->updateOrCreate(
            ['type' => 'video', 'name' => 'Video en vivo'],
            [
                'format' => 'youtube',
                'url' => null,
                'is_active' => false,
                'is_primary' => true,
                'sort_order' => 2,
            ],
        );
    }
}
