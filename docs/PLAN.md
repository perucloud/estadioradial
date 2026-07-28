# Plan de desarrollo

## Objetivo

Construir una plataforma autoadministrable de noticias y radio en línea, basada
en Laravel y preparada para instalarse en hosting compartido. La copia de
HTTrack se conserva únicamente como referencia visual en `oldweb/`.

## Primera versión pública

- [ ] Layout general: cabecera, navegación, reproductor y pie.
- [ ] Portada dinámica.
- [ ] Listado y detalle de noticias mediante slugs.
- [ ] Listado de noticias por categoría.
- [ ] Listado y detalle de programas mediante slugs.
- [ ] Programación semanal.
- [ ] Radio en vivo configurable.
- [ ] Video en vivo opcional y configurable.
- [ ] Diseño adaptable a escritorio, tableta y móvil.
- [ ] SEO técnico básico.
- [ ] Base para convertir todo el portal público en PWA.

## Administración

- [ ] Inicio de sesión.
- [ ] Usuarios y roles de administrador/editor.
- [ ] Noticias, categorías y etiquetas.
- [ ] Programas, conductores y horarios.
- [ ] Biblioteca multimedia.
- [ ] Configuración de audio y video streaming.
- [ ] Banners y secciones destacadas de la portada.
- [ ] Datos institucionales, redes sociales y SEO.

## Etapas

### 0. Preparación

- [x] Inventariar la descarga original.
- [x] Mover la descarga completa a `oldweb/`.
- [x] Crear Laravel en la raíz.
- [x] Inicializar Git y enlazar el repositorio remoto.
- [x] Verificar instalación y pruebas iniciales.

### 1. Base pública

- [ ] Definir identidad visual provisional.
- [ ] Construir componentes compartidos.
- [ ] Crear portada con contenido demostrativo.
- [ ] Crear páginas internas prioritarias.
- [ ] Implementar reproductor persistente.

### 2. Contenido administrable

- [ ] Diseñar migraciones y modelos.
- [ ] Construir el panel administrativo.
- [ ] Sustituir el contenido demostrativo por datos administrables.

### 3. PWA y producción

- [ ] Manifest e iconos.
- [ ] Service worker y página sin conexión.
- [ ] Media Session para el reproductor.
- [ ] Pruebas de instalación en Android.
- [ ] Documentar y validar despliegue en hosting compartido.

## Fuera del alcance inicial

- Aplicaciones nativas para Android o iOS.
- Notificaciones push.
- Monetización o redes publicitarias.
- Retransmisión del stream a través del servidor Laravel.
- Importación automática masiva de contenidos de terceros.
