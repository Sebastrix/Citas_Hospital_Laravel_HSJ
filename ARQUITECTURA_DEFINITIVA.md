# Arquitectura definitiva

```text
Laravel 13
├── PHP 8.4
├── Blade + HTML
├── Tailwind CSS + Preline
├── JavaScript
├── sqlsrv → Microsoft SQL Server local → CITAS
└── sigh   → Microsoft SQL Server remoto → SIGH
```

No se utiliza MySQL en esta versión.

CITAS mantiene la información propia del portal (sala de espera, registros y logs). SIGH se conserva como conexión clínica remota.
