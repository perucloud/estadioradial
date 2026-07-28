<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Program;
use App\Models\Schedule;
use App\Models\Stream;
use Illuminate\Database\Seeder;

class PortalSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            ['name' => 'Actualidad', 'slug' => 'actualidad', 'color' => '#e5261f'],
            ['name' => 'Política', 'slug' => 'politica', 'color' => '#d91f18'],
            ['name' => 'Nacional', 'slug' => 'nacional', 'color' => '#ef5b2a'],
            ['name' => 'Cultura', 'slug' => 'cultura', 'color' => '#8b3fc7'],
            ['name' => 'Deportes', 'slug' => 'deportes', 'color' => '#18895b'],
        ])->mapWithKeys(function (array $data) {
            $category = Category::query()->updateOrCreate(['slug' => $data['slug']], $data);

            return [$data['slug'] => $category];
        });

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
            ],
            [
                'category' => 'politica',
                'title' => 'Autoridades regionales presentan agenda de trabajo para el segundo semestre',
                'slug' => 'autoridades-regionales-presentan-agenda-de-trabajo',
                'excerpt' => 'La propuesta prioriza servicios públicos, conectividad y proyectos de alcance regional.',
                'body' => '<p>Representantes regionales presentaron una agenda conjunta para coordinar los proyectos prioritarios del segundo semestre.</p><p>Las mesas técnicas continuarán durante las próximas semanas.</p>',
                'image' => '/images/demo/news-government.svg',
                'author' => 'Mesa de noticias',
                'featured' => true,
                'minutes_ago' => 35,
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
            ],
        ];

        foreach ($posts as $item) {
            Post::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'category_id' => $categories[$item['category']]->id,
                    'title' => $item['title'],
                    'excerpt' => $item['excerpt'],
                    'body' => $item['body'],
                    'image' => $item['image'],
                    'author' => $item['author'],
                    'status' => 'published',
                    'is_featured' => $item['featured'],
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
        ])->map(function (array $data) {
            return Program::query()->updateOrCreate(
                ['slug' => $data['slug']],
                $data + ['is_active' => true],
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
                'sort_order' => 1,
            ],
        );

        Stream::query()->updateOrCreate(
            ['type' => 'video', 'name' => 'Video en vivo'],
            [
                'format' => 'youtube',
                'url' => null,
                'is_active' => false,
                'sort_order' => 2,
            ],
        );
    }
}
