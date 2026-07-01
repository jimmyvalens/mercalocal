# Mercalocal 🏪

[![PHP Version](https://img.shields.io/badge/php-%5E8.3-777bb4.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/mysql-%2300f.svg?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Tailwind CSS](https://img.shields.io/badge/tailwindcss-%2338B2AC.svg?style=flat&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)

**Mercalocal** es una plataforma tipo marketplace diseñada específicamente para la digitalización, visibilidad y dinamización de comercios, negocios y profesionales locales. Este proyecto ha sido desarrollado como el **Trabajo de Fin de Grado (TFG)** para el Ciclo Superior de Desarrollo de Aplicaciones Web (DAW).

---

## 🚀 Enfoque del Proyecto & Arquitectura

A diferencia de los enfoques tradicionales basados en frameworks pesados y complejos, **Mercalocal** se ha construido bajo una filosofía de **código limpio, directo y mantenible**, optimizado para el desarrollo independiente (*solopreneur*). 

* **Arquitectura Limpia (Vanilla PHP):** Implementación de un patrón estructurado con un núcleo (*Core*) desacoplado.
* **Seguridad y Eficiencia:** Abstracción completa de la base de datos mediante **PDO** con consultas preparadas para mitigar inyecciones SQL (SQLi).
* **Modularidad:** Enrutador personalizado nativo, controladores enfocados y separación estricta de responsabilidades.
* **Interfaz Moderna:** Maquetación ágil, responsiva y ligera utilizando **Tailwind CSS**.

---

## 🛠️ Stack Tecnológico

* **Backend:** PHP Puro 8.3 sin frameworks (No Laravel/Symfony).
* **Base de Datos:** MySQL con motor InnoDB y persistencia de datos segura mediante PDO.
* **Frontend:** HTML5, JavaScript nativo y Tailwind CSS (mediante su CLI/compilación nativa).
* **Herramientas clave de desarrollo:** Git para el control de versiones.

---

## 📁 Estructura del Proyecto

El repositorio sigue una estructura de directorios intuitiva y organizada por capas:

```text
mercalocal/
├── app/
│   ├── Core/         # Núcleo del sistema (Enrutador, Base de Datos, Mailer)
│   ├── Controllers/  # Controladores de la lógica de negocio
│   └── Models/       # Capa de datos y consultas SQL (PDO)
├── public/           # Único punto de entrada accesible a la web
│   └── index.php     # Front Controller
├── views/            # Plantillas y vistas de la interfaz de usuario
├── logs/             # Registros del sistema (simulación de correo, etc.)
└── README.md         # Documentación del proyecto

```

---

## ⚙️ Requisitos e Instalación

### Requisitos previos

* Servidor local con soporte para **PHP 8.3+ (XAMPP, Laragon, o entorno nativo).
* Servidor de bases de datos **MySQL**.

### Pasos para la instalación local

1. **Clonar el repositorio:**
```bash
git clone [https://github.com/jimmyvalens/mercalocal.git](https://github.com/jimmyvalens/mercalocal.git)
cd mercalocal

```


2. **Configurar el entorno web:**
Asegúrate de apuntar la raíz (*DocumentRoot*) de tu servidor web (Apache/Nginx) a la carpeta `public/` del proyecto para garantizar el correcto funcionamiento del enrutador y la seguridad de las carpetas internas.
3. **Configurar la Base de Datos:**
* Crea una base de datos en MySQL llamada `mercalocal`.
* Importa el archivo de estructura (si añades un archivo .sql, pon el nombre aquí, por ejemplo: `database/structure.sql`).
* Configura las credenciales de conexión en tu archivo de configuración del núcleo (`app/Core/...`).



---

## 📧 Funcionalidades Destacadas (En Desarrollo / Producción)

* **Directorio de Negocios:** Buscador y filtros avanzados para clasificar profesionales y comercios de la localidad.
* **Panel de Gestión:** Espacio privado para que los comerciantes locales gestionen su perfil, productos y horarios.
* **Núcleo Extensible (Mailer/Logs):** Sistema preparado para la simulación y envío de notificaciones por correo electrónico estructurado para futuras integraciones de servicios SMTP comerciales.

---

## 👤 Autor

* **Jimmy** - *Desarrollador e Idea Original* - Alumno del ciclo superior DAW en I.E.S. Albarregas, Mérida-España.

