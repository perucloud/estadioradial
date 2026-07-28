<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CurrentNewsSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::query()->get()->keyBy('slug');

        foreach ($this->news() as $item) {
            $post = Post::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'category_id' => $categories->get($item['category'])->id,
                    'title' => $item['title'],
                    'excerpt' => $item['excerpt'],
                    'body' => collect($item['paragraphs'])
                        ->map(fn (string $paragraph) => '<p>'.e($paragraph).'</p>')
                        ->implode(''),
                    'image' => $item['image'],
                    'author' => 'Redacción Estación Radial',
                    'status' => 'published',
                    'is_featured' => $item['featured'] ?? false,
                    'views_count' => $item['views'],
                    'source_name' => $item['source_name'],
                    'source_url' => $item['source_url'],
                    'image_credit' => 'Ilustración: Estación Radial',
                    'image_license' => 'Recurso gráfico propio',
                    'editorial_priority' => $item['priority'],
                    'is_homepage_hidden' => false,
                    'pinned_until' => $item['pinned_until'] ?? null,
                    'published_at' => $item['published_at'],
                ],
            );

            $tagIds = collect($item['tags'])
                ->map(function (string $name) {
                    return Tag::query()->firstOrCreate(
                        ['slug' => Str::slug($name)],
                        ['name' => $name],
                    )->id;
                });

            $post->tags()->sync($tagIds);
        }
    }

    /**
     * Selección editorial inicial. Los textos son resúmenes propios y las fuentes
     * se guardan para trazabilidad, contraste y actualización posterior.
     */
    private function news(): array
    {
        return [
            [
                'category' => 'politica',
                'title' => 'Keiko Fujimori asume la Presidencia en una jornada marcada por el cambio de mando',
                'slug' => 'keiko-fujimori-asume-presidencia-cambio-de-mando-2026',
                'excerpt' => 'La ceremonia del 28 de julio abre un nuevo periodo de gobierno y concentra la atención en el mensaje presidencial y las primeras decisiones del Ejecutivo.',
                'paragraphs' => [
                    'Keiko Fujimori asume la Presidencia de la República este 28 de julio, en una jornada oficial que incluye la transmisión de mando ante el Congreso y la participación de delegaciones extranjeras.',
                    'El inicio del nuevo gobierno pone el foco en la composición del gabinete, el rumbo de la política exterior y las prioridades que se anuncien para seguridad, economía y servicios públicos. Esta nota resume información pública disponible y deberá actualizarse conforme se publiquen los actos y documentos oficiales.',
                ],
                'image' => '/images/demo/news-government.svg',
                'source_name' => 'El País',
                'source_url' => 'https://elpais.com/america/2026-07-28/toma-de-mando-de-keiko-fujimori-cuando-es-que-jefes-de-estado-asisten-y-la-agenda-oficial.html',
                'published_at' => '2026-07-28 09:30:00',
                'priority' => 100,
                'views' => 9850,
                'featured' => true,
                'pinned_until' => '2026-07-29 23:59:59',
                'tags' => ['Keiko Fujimori', 'Cambio de mando', 'Poder Ejecutivo'],
            ],
            [
                'category' => 'politica',
                'title' => 'El Senado inicia su primera legislatura y plantea escuchar antes de decidir',
                'slug' => 'senado-inicia-primera-legislatura-2026-2027',
                'excerpt' => 'Miguel Torres asumió la presidencia del Senado en el retorno del sistema bicameral y señaló que la deliberación marcará la gestión.',
                'paragraphs' => [
                    'El Senado de la República declaró instalada su primera legislatura del periodo 2026-2027, como parte del retorno del Congreso bicameral.',
                    'En su discurso de asunción, Miguel Torres sostuvo que la nueva cámara debe escuchar y reflexionar antes de legislar. La instalación abre una etapa en la que Senado y Cámara de Diputados deberán coordinar sus funciones y procedimientos.',
                ],
                'image' => '/images/demo/news-government.svg',
                'source_name' => 'Congreso de la República',
                'source_url' => 'https://comunicaciones.congreso.gob.pe/',
                'published_at' => '2026-07-27 18:40:00',
                'priority' => 94,
                'views' => 7420,
                'featured' => true,
                'tags' => ['Senado', 'Congreso bicameral', 'Miguel Torres'],
            ],
            [
                'category' => 'politica',
                'title' => 'Cámara de Diputados anuncia austeridad y agenda contra la inseguridad',
                'slug' => 'camara-diputados-anuncia-austeridad-agenda-inseguridad',
                'excerpt' => 'Óscar Reto afirmó que su gestión buscará limitar el dispendio y atender demandas sobre delincuencia, educación, salud y empleo.',
                'paragraphs' => [
                    'Óscar Reto, presidente de la Cámara de Diputados para el periodo 2026-2027, anunció una política de austeridad institucional y un trabajo orientado a combatir la corrupción y la inseguridad ciudadana.',
                    'El titular de la cámara también mencionó educación, salud, empleo, apoyo al campo y prevención ante El Niño Costero. Su discurso planteó que la fiscalización debe convivir con el diálogo y el equilibrio de poderes.',
                ],
                'image' => '/images/demo/news-government.svg',
                'source_name' => 'Diario Oficial El Peruano',
                'source_url' => 'https://elperuano.pe/noticia/301113-presidente-de-camara-de-diputados-anuncia-politica-de-austeridad-y-trabajo-contra-la-delincuencia',
                'published_at' => '2026-07-26 21:28:00',
                'priority' => 92,
                'views' => 6840,
                'featured' => true,
                'tags' => ['Cámara de Diputados', 'Óscar Reto', 'Seguridad ciudadana'],
            ],
            [
                'category' => 'economia',
                'title' => 'Economía peruana suma 26 meses de crecimiento y avanza 3,2 % entre enero y mayo',
                'slug' => 'economia-peruana-26-meses-crecimiento-enero-mayo-2026',
                'excerpt' => 'El MEF atribuye la recuperación a la demanda interna; construcción, comercio y servicios impulsaron la actividad no primaria en mayo.',
                'paragraphs' => [
                    'La economía peruana acumuló 26 meses consecutivos de crecimiento. Entre enero y mayo de 2026 avanzó 3,2 %, mientras que el producto bruto interno de mayo creció 1,8 %, según el Ministerio de Economía y Finanzas.',
                    'La actividad no primaria aumentó 4,3 % en mayo, con aportes de construcción, comercio y servicios. El reporte también señala un incremento de 7,5 % en el empleo de Lima Metropolitana, un indicador relevante para medir la llegada de la recuperación a los hogares.',
                ],
                'image' => '/images/demo/news-economy.svg',
                'source_name' => 'Ministerio de Economía y Finanzas',
                'source_url' => 'https://www.gob.pe/institucion/mef/noticias/1419328-mef-economia-peruana-mantiene-26-meses-de-crecimiento-continuo-y-consolida-su-recuperacion-impulsada-por-la-demanda-interna',
                'published_at' => '2026-07-15 14:10:00',
                'priority' => 90,
                'views' => 6280,
                'featured' => true,
                'tags' => ['PBI', 'Crecimiento económico', 'MEF'],
            ],
            [
                'category' => 'regionales',
                'title' => 'Ocho regiones concentran proyectos de agua y saneamiento por más de US$ 2.500 millones',
                'slug' => 'ocho-regiones-proyectos-agua-saneamiento-2500-millones',
                'excerpt' => 'La cartera comprende iniciativas para Madre de Dios, Cajamarca, Lima, San Martín, Moquegua, La Libertad, Junín y Cusco.',
                'paragraphs' => [
                    'El Gobierno proyectó una cartera de ocho asociaciones público-privadas de agua y saneamiento por US$ 2.529 millones, destinada a ocho regiones del país.',
                    'Entre las iniciativas figuran una planta desaladora para Ilo y plantas de tratamiento de aguas residuales para Trujillo. La ejecución y los plazos deben seguirse por proyecto, pues la cartera agrupa intervenciones con alcances y etapas distintas.',
                ],
                'image' => '/images/demo/news-community.svg',
                'source_name' => 'Ministerio de Economía y Finanzas',
                'source_url' => 'https://www.gob.pe/institucion/mef/noticias/1284533-a-julio-de-2026-gobierno-proyecta-adjudicar-ocho-proyectos-app-de-saneamiento-por-mas-de-us-2500-millones-en-beneficio-de-ocho-regiones',
                'published_at' => '2026-07-24 10:20:00',
                'priority' => 89,
                'views' => 5190,
                'tags' => ['Agua y saneamiento', 'Inversión regional', 'Obras públicas'],
            ],
            [
                'category' => 'cultura',
                'title' => 'Lima celebra Fiestas Patrias con agenda cultural, gastronómica y artística',
                'slug' => 'lima-agenda-cultural-gastronomica-fiestas-patrias-2026',
                'excerpt' => 'La programación municipal reúne actividades para públicos diversos y propone redescubrir la ciudad durante el feriado.',
                'paragraphs' => [
                    'La Municipalidad Metropolitana de Lima presentó una agenda de actividades culturales, artísticas y gastronómicas por Fiestas Patrias 2026.',
                    'La programación busca ofrecer alternativas para residentes y visitantes, así como promover el encuentro ciudadano y la valoración de expresiones culturales peruanas. Horarios y condiciones de ingreso deben verificarse en la agenda oficial antes de asistir.',
                ],
                'image' => '/images/demo/news-culture.svg',
                'source_name' => 'Municipalidad Metropolitana de Lima',
                'source_url' => 'https://www.gob.pe/institucion/munilima/noticias/1422572-lima-celebra-al-peru-con-una-gran-agenda-cultural-gastronomica-y-artistica-para-vivir-las-fiestas-patrias-2026',
                'published_at' => '2026-07-24 09:00:00',
                'priority' => 85,
                'views' => 7080,
                'tags' => ['Fiestas Patrias', 'Agenda cultural', 'Lima'],
            ],
            [
                'category' => 'regionales',
                'title' => 'Cultura evalúa daños al patrimonio de Junín tras sismo de magnitud 5,1',
                'slug' => 'cultura-evalua-danos-patrimonio-junin-sismo-5-1',
                'excerpt' => 'Equipos especializados revisan bienes culturales después del movimiento con epicentro en Chongos Bajo, provincia de Chupaca.',
                'paragraphs' => [
                    'El Ministerio de Cultura continúa evaluando posibles daños en el patrimonio cultural de Junín luego del sismo de magnitud 5,1 registrado el 18 de julio, con epicentro en Chongos Bajo, Chupaca.',
                    'La revisión está a cargo del centro de operaciones del sector y la Dirección Desconcentrada de Cultura. El diagnóstico permitirá definir intervenciones y medidas de protección según el estado de los bienes inspeccionados.',
                ],
                'image' => '/images/demo/news-culture.svg',
                'source_name' => 'Ministerio de Cultura',
                'source_url' => 'https://www.gob.pe/institucion/cultura/noticias/1421040-ministerio-de-cultura-continua-evaluando-los-danos-al-patrimonio-cultural-tras-el-sismo-registrado-en-junin',
                'published_at' => '2026-07-20 12:16:00',
                'priority' => 84,
                'views' => 4960,
                'tags' => ['Junín', 'Patrimonio cultural', 'Sismo'],
            ],
            [
                'category' => 'deportes',
                'title' => 'Perú cierra los Parasuramericanos de Valledupar con 41 medallas',
                'slug' => 'peru-parasuramericanos-valledupar-41-medallas',
                'excerpt' => 'La delegación nacional alcanzó su mejor participación histórica en el certamen y fue reconocida por el IPD.',
                'paragraphs' => [
                    'Perú concluyó su participación en los Juegos Parasuramericanos Valledupar 2026 con 41 medallas, resultado presentado por el IPD como la mejor actuación nacional en la historia del torneo.',
                    'El balance refuerza la atención sobre el acompañamiento técnico, la infraestructura y la continuidad de apoyo a los paradeportistas que integran el ciclo competitivo internacional.',
                ],
                'image' => '/images/demo/news-sports.svg',
                'source_name' => 'Instituto Peruano del Deporte',
                'source_url' => 'https://www.gob.pe/institucion/ipd/noticias',
                'published_at' => '2026-07-24 13:57:00',
                'priority' => 83,
                'views' => 7710,
                'tags' => ['Parasuramericanos', 'Valledupar 2026', 'Paradeporte'],
            ],
            [
                'category' => 'deportes',
                'title' => 'Infraestructura deportiva recibirá más de S/ 145 millones rumbo a Lima 2027',
                'slug' => 'infraestructura-deportiva-145-millones-lima-2027',
                'excerpt' => 'Un convenio entre el IPD y la ANIN contempla modernizar el Estadio Nacional, el Coliseo Dibós y crear un nuevo centro deportivo.',
                'paragraphs' => [
                    'El Instituto Peruano del Deporte informó de un convenio con la Autoridad Nacional de Infraestructura para ejecutar más de S/ 145 millones en escenarios deportivos.',
                    'El plan incluye mejoras en recintos emblemáticos como el Estadio Nacional y el Coliseo Eduardo Dibós, además de un nuevo centro. Las intervenciones forman parte de la preparación de Lima para los Juegos Panamericanos y Parapanamericanos de 2027.',
                ],
                'image' => '/images/demo/news-sports.svg',
                'source_name' => 'Instituto Peruano del Deporte',
                'source_url' => 'https://www.gob.pe/institucion/ipd/noticias',
                'published_at' => '2026-07-22 17:58:00',
                'priority' => 80,
                'views' => 6120,
                'tags' => ['Lima 2027', 'Infraestructura deportiva', 'IPD'],
            ],
            [
                'category' => 'deportes',
                'title' => 'Juegos Panamericanos Universitarios reúnen competencia internacional en Lima',
                'slug' => 'juegos-panamericanos-universitarios-lima-2026',
                'excerpt' => 'La capital peruana alberga por primera vez esta competencia y utiliza sedes como la Videna y el complejo de la Costa Verde.',
                'paragraphs' => [
                    'Lima recibe los IV Juegos Panamericanos Universitarios 2026, una competencia que convoca a deportistas universitarios de distintos países del continente.',
                    'La actividad distribuye pruebas en escenarios deportivos de la capital. El evento funciona además como oportunidad de preparación operativa y uso de infraestructura con miras al calendario deportivo de Lima 2027.',
                ],
                'image' => '/images/demo/news-sports.svg',
                'source_name' => 'Diario Oficial El Peruano',
                'source_url' => 'https://www.elperuano.pe/noticia/295941-peru-albergara-por-primera-vez-los-iv-panamericanos-universitarios-lima-2026',
                'published_at' => '2026-07-21 07:43:00',
                'priority' => 78,
                'views' => 5830,
                'tags' => ['Deporte universitario', 'Lima 2026', 'Panamericanos'],
            ],
            [
                'category' => 'economia',
                'title' => 'BCR eleva a 3,4 % su proyección de crecimiento para 2026',
                'slug' => 'bcr-eleva-proyeccion-crecimiento-3-4-2026',
                'excerpt' => 'La revisión considera un mayor avance de la inversión privada y del consumo, dos componentes centrales de la demanda interna.',
                'paragraphs' => [
                    'El Banco Central de Reserva revisó al alza su proyección de crecimiento de la economía peruana para 2026 y la situó en 3,4 %.',
                    'La estimación incorpora un desempeño más favorable de la inversión privada y el consumo. Como toda proyección, está sujeta a cambios en el contexto internacional, las expectativas empresariales y la ejecución de inversiones.',
                ],
                'image' => '/images/demo/news-economy.svg',
                'source_name' => 'Agencia Andina',
                'source_url' => 'https://andina.pe/agencia/noticia-bcr-revisa-al-alza-proyeccion-crecimiento-de-economia-peruana-a-34-para-2026-1080154.aspx',
                'published_at' => '2026-07-18 11:30:00',
                'priority' => 77,
                'views' => 5590,
                'tags' => ['BCR', 'Proyecciones', 'Inversión privada'],
            ],
            [
                'category' => 'economia',
                'title' => 'Exportaciones peruanas superan US$ 45.100 millones en los primeros cinco meses',
                'slug' => 'exportaciones-peruanas-45128-millones-primeros-cinco-meses-2026',
                'excerpt' => 'Los envíos crecieron 37 % y llegaron a 162 mercados, de acuerdo con el balance difundido por la Agencia Andina.',
                'paragraphs' => [
                    'Las exportaciones peruanas sumaron US$ 45.128 millones entre enero y mayo de 2026, un crecimiento de 37 % frente al mismo periodo previo.',
                    'Los productos nacionales llegaron a 162 países. Para interpretar el resultado conviene observar tanto el volumen exportado como los precios internacionales y la participación de sectores tradicionales y no tradicionales.',
                ],
                'image' => '/images/demo/news-economy.svg',
                'source_name' => 'Agencia Andina',
                'source_url' => 'https://andina.pe/agencia/noticia-exportaciones-peruanas-suman-45128-millones-primeros-cinco-meses-y-crecen-37-1083039.aspx',
                'published_at' => '2026-07-17 15:00:00',
                'priority' => 74,
                'views' => 5320,
                'tags' => ['Exportaciones', 'Comercio exterior', 'Empresas'],
            ],
            [
                'category' => 'educacion',
                'title' => 'Obras por Impuestos podría mejorar condiciones en más de 26 mil colegios',
                'slug' => 'obras-impuestos-servicios-educativos-26-mil-colegios',
                'excerpt' => 'Los nuevos servicios permitirían financiar intervenciones frente a bajas temperaturas y mejorar ambientes para estudiantes y docentes.',
                'paragraphs' => [
                    'Proinversión informó que la ampliación de servicios ejecutables mediante Obras por Impuestos podría mejorar las condiciones de aprendizaje en más de 26 mil instituciones educativas.',
                    'Las intervenciones incluyen medidas para contar con escuelas más seguras y adecuadas frente al frío. Desde 2009, Educación representa 24 % de la inversión adjudicada bajo este mecanismo, con compromisos por S/ 5.159 millones.',
                ],
                'image' => '/images/demo/news-education.svg',
                'source_name' => 'Proinversión',
                'source_url' => 'https://www.gob.pe/institucion/proinversion/noticias/1417944-proinversion-nuevos-servicios-mediante-oxi-podrian-mejorar-las-condiciones-de-aprendizaje-en-mas-de-26-mil-colegios',
                'published_at' => '2026-07-13 10:45:00',
                'priority' => 72,
                'views' => 4290,
                'tags' => ['Colegios', 'Obras por Impuestos', 'Bajas temperaturas'],
            ],
            [
                'category' => 'cultura',
                'title' => 'Museos Abiertos convocó a más de 24 mil visitantes en todo el país',
                'slug' => 'museos-abiertos-24-mil-visitantes-julio-2026',
                'excerpt' => 'La edición de julio permitió el ingreso gratuito de peruanos y residentes extranjeros a 56 museos.',
                'paragraphs' => [
                    'La jornada nacional Museos Abiertos recibió a 24.432 visitantes durante el primer domingo de julio, según el Ministerio de Cultura.',
                    'La iniciativa habilitó el acceso gratuito a 56 museos para ciudadanos peruanos y residentes extranjeros. Su objetivo es acercar el patrimonio a nuevos públicos y fortalecer el vínculo de las comunidades con sus espacios culturales.',
                ],
                'image' => '/images/demo/news-culture.svg',
                'source_name' => 'Ministerio de Cultura',
                'source_url' => 'https://www.gob.pe/institucion/cultura/noticias/1416301-ministerio-de-cultura-museos-abiertos-reunio-a-mas-de-24-mil-visitantes-en-una-jornada-nacional-de-reencuentro-con-nuestra-identidad',
                'published_at' => '2026-07-08 12:17:00',
                'priority' => 70,
                'views' => 4870,
                'tags' => ['Museos Abiertos', 'Patrimonio', 'Acceso cultural'],
            ],
            [
                'category' => 'nacional',
                'title' => 'Población ocupada creció 1,3 % en el primer trimestre de 2026',
                'slug' => 'poblacion-ocupada-crecio-primer-trimestre-2026',
                'excerpt' => 'El INEI calculó 17,6 millones de personas con empleo a nivel nacional durante el periodo analizado.',
                'paragraphs' => [
                    'La población ocupada del país aumentó 1,3 % en el primer trimestre de 2026 y alcanzó aproximadamente 17,6 millones de personas, de acuerdo con el INEI.',
                    'El indicador permite observar la evolución general del empleo, aunque su lectura debe complementarse con información sobre informalidad, ingresos, horas trabajadas y diferencias entre áreas urbanas y rurales.',
                ],
                'image' => '/images/demo/news-community.svg',
                'source_name' => 'Instituto Nacional de Estadística e Informática',
                'source_url' => 'https://www.gob.pe/institucion/inei/noticias/1392128-poblacion-ocupada-a-nivel-nacional-se-incremento-1-3-en-el-primer-trimestre-de-2026',
                'published_at' => '2026-07-16 08:30:00',
                'priority' => 68,
                'views' => 4520,
                'tags' => ['Empleo', 'INEI', 'Mercado laboral'],
            ],
            [
                'category' => 'nacional',
                'title' => 'Perú registra 34,1 millones de habitantes según resultados censales',
                'slug' => 'peru-34-millones-habitantes-resultados-censales',
                'excerpt' => 'El nuevo dato poblacional sirve de base para planificar servicios, inversión pública y representación territorial.',
                'paragraphs' => [
                    'El INEI informó que la población del Perú totalizó 34.157.732 habitantes en 2025, de acuerdo con los resultados difundidos del proceso censal.',
                    'La actualización es relevante para la planificación de salud, educación, transporte y servicios básicos. El análisis territorial permitirá observar con mayor detalle la distribución y los cambios demográficos entre regiones.',
                ],
                'image' => '/images/demo/news-community.svg',
                'source_name' => 'Instituto Nacional de Estadística e Informática',
                'source_url' => 'https://www.gob.pe/es/institucion/inei/noticias/1399446-inei-population-of-peru-totalized-34-million-157-thousand-732-inhabitants-as-of-2025',
                'published_at' => '2026-07-12 09:15:00',
                'priority' => 66,
                'views' => 4730,
                'tags' => ['Censo', 'Población', 'INEI'],
            ],
            [
                'category' => 'nacional',
                'title' => 'Servicios por Impuestos apunta a salud, educación y agua en zonas vulnerables',
                'slug' => 'servicios-por-impuestos-salud-educacion-agua-zonas-vulnerables',
                'excerpt' => 'La modalidad permite financiar servicios esenciales en áreas rurales, de frontera o declaradas en emergencia.',
                'paragraphs' => [
                    'Proinversión presentó Servicios por Impuestos, una modalidad vinculada a Obras por Impuestos para financiar y ejecutar intervenciones medibles en salud, educación y saneamiento.',
                    'El mecanismo está orientado a zonas rurales, fronterizas o en emergencia y no reemplaza proyectos de inversión ni gastos corrientes permanentes. Su aplicación requerirá objetivos verificables y seguimiento de resultados.',
                ],
                'image' => '/images/demo/news-health.svg',
                'source_name' => 'Proinversión',
                'source_url' => 'https://www.gob.pe/institucion/proinversion/noticias/1407182-proinversion-impulsa-servicios-por-impuestos-para-llevar-salud-educacion-y-agua-segura-a-las-zonas-mas-vulnerables-del-pais',
                'published_at' => '2026-07-10 11:00:00',
                'priority' => 64,
                'views' => 3980,
                'tags' => ['Servicios públicos', 'Zonas rurales', 'Proinversión'],
            ],
            [
                'category' => 'locales',
                'title' => 'Lima activa plan de seguridad vial y respuesta a emergencias por Fiestas Patrias',
                'slug' => 'lima-plan-transita-seguro-fiestas-patrias-2026',
                'excerpt' => 'EMAPE coordinará con la PNP, Sutran, ATU, Bomberos y SAMU para reforzar el tránsito y la atención de incidentes.',
                'paragraphs' => [
                    'La Municipalidad de Lima puso en marcha el plan Transita Seguro con EMAPE durante las celebraciones de Fiestas Patrias.',
                    'La intervención articula a entidades de transporte, seguridad y emergencia para atender incidentes, controlar el tránsito y reforzar la seguridad vial. Los usuarios deben consultar los canales oficiales ante cierres o desvíos temporales.',
                ],
                'image' => '/images/demo/news-transport.svg',
                'source_name' => 'Municipalidad Metropolitana de Lima',
                'source_url' => 'https://www.gob.pe/institucion/munilima/noticias/1422788-municipalidad-de-lima-pone-en-marcha-el-plan-transita-seguro-con-emape-por-fiestas-patrias',
                'published_at' => '2026-07-24 11:29:00',
                'priority' => 86,
                'views' => 5240,
                'tags' => ['Lima', 'Seguridad vial', 'Fiestas Patrias'],
            ],
            [
                'category' => 'regionales',
                'title' => 'Piura inaugura puente en Loma Negra para mejorar la conexión del Bajo Piura',
                'slug' => 'piura-puente-loma-negra-conectividad-bajo-piura',
                'excerpt' => 'La infraestructura busca dar tránsito más seguro y facilitar el transporte agrícola para más de 500 habitantes.',
                'paragraphs' => [
                    'La Municipalidad Provincial de Piura informó de la inauguración de un puente en Loma Negra, obra destinada a mejorar la conectividad de comunidades del Bajo Piura.',
                    'La infraestructura beneficiaría a más de 500 habitantes y facilitaría el traslado de productos agrícolas. El seguimiento local deberá considerar su mantenimiento y el impacto efectivo en tiempos y seguridad de viaje.',
                ],
                'image' => '/images/demo/news-transport.svg',
                'source_name' => 'Municipalidad Provincial de Piura',
                'source_url' => 'https://www.gob.pe/institucion/munipiura/noticias',
                'published_at' => '2026-07-27 07:45:00',
                'priority' => 87,
                'views' => 5110,
                'tags' => ['Piura', 'Bajo Piura', 'Conectividad'],
            ],
            [
                'category' => 'deportes',
                'title' => 'Día del Deporte Nacional promueve actividad física para todas las edades',
                'slug' => 'dia-deporte-nacional-actividad-fisica-todas-edades',
                'excerpt' => 'Una jornada del programa Actívate con el IPD reunió a escolares, adultos y personas mayores en La Victoria.',
                'paragraphs' => [
                    'El Perú conmemoró el Día del Deporte Nacional con actividades orientadas a promover la práctica física y los estilos de vida saludables.',
                    'El programa Actívate con el IPD reunió a escolares, adultos y personas mayores en sesiones recreativas dirigidas por profesionales. La fecha, establecida por ley, busca reconocer el deporte como herramienta de bienestar e integración.',
                ],
                'image' => '/images/demo/news-sports.svg',
                'source_name' => 'Instituto Peruano del Deporte',
                'source_url' => 'https://www.gob.pe/institucion/ipd/noticias/1419117-dia-del-deporte-nacional-ipd-reafirmo-su-compromiso-de-impulsar-una-vida-activa-y-saludable-en-el-pais',
                'published_at' => '2026-07-15 11:50:00',
                'priority' => 62,
                'views' => 3690,
                'tags' => ['Actividad física', 'Vida saludable', 'IPD'],
            ],
        ];
    }
}
