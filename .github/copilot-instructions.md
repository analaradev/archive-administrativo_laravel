# instrucciones para el uso de GitHub Copilot



## Contexto general

Necesito que cada vez que te dé un prompt que inicie con la palabra clave @issue, realices el siguiente flujo de trabajo de manera secuencial.



Primero, crea un "issue" con MCP en el repositorio de GitHub. El título del "issue" debe ser un resumen claro y conciso de mi solicitud, y en el cuerpo del mismo debes incluir una descripción detallada de la tarea a realizar o el problema a solucionar, basándote en la información que te proporciono en el prompt.



A continuación, una vez creado el "issue", genera una nueva rama ("branch") con MCP para empezar a trabajar en su solución. 


Después, crea un "Pull Request" (PR) con MCP desde la nueva rama que generaste, apuntando hacia la rama del repositorio (dev). El título del PR debe ser descriptivo, idealmente el mismo que el del "issue". En el cuerpo del PR, asegúrate de incluir una referencia que cierre automáticamente el "issue" correspondiente cuando el PR se fusione, utilizando una palabra clave como Closes #[numero-del-issue].



Finalmente, una vez que toda la estructura en GitHub (issue, branch y PR) esté lista, procede a desarrollar la solución. Analiza nuevamente la descripción detallada del "issue" que creaste y comienza a generar y proponer los cambios en el código necesarios para resolver la tarea. Asegúrate de que todo el trabajo y las modificaciones de código se realicen exclusivamente dentro de la rama que acabas de crear para esta tarea.



## Contexto sobre cierre de PR

Cuando en el chat ponga @endpr debes cerrar el PR que has creado previamente asociado a la rama actual. Asegurate que no existan cambios pendientes por subir. Asegúrate de que el PR esté completamente listo para ser cerrado, ya sea porque se ha completado la tarea o porque se ha decidido no continuar con ella. Utiliza una frase clara en el comentario de cierre. Genera merge commit si es necesario, pero asegúrate de que el PR esté limpio y no contenga conflictos. Si el PR está asociado a un "issue", asegúrate de que el "issue" se cierre automáticamente al cerrar el PR, utilizando la sintaxis adecuada en el comentario de cierre (por ejemplo, Closes #[numero-del-issue]).

## Contexto sobre que hago yo y quien soy yo

Soy un desarrollador backend por lo que no requiero que tu agente realice tareas de frontend. Mi enfoque está en el desarrollo de la lógica del servidor, la gestión de bases de datos y la implementación de APIs. Por lo tanto, cuando me proporciones código o soluciones, asegúrate de que estén alineados con estas áreas y evita sugerencias relacionadas con el diseño o la interfaz de usuario. Recuerda usar buenas prácticas de programación y seguir las convenciones del lenguaje y del framework que estemos utilizando.

## Contexto sobre pruebas

Por cada @issue que se cree, de ser necesario debes generar un test unitario que valide el correcto funcionamiento de la funcionalidad o corrección implementada. El test debe ser claro y específico, asegurando que cubra los casos más relevantes relacionados con el "issue". Utiliza un framework de pruebas adecuado para el lenguaje y el entorno de desarrollo que estemos utilizando. Asegúrate de que el test sea fácil de entender y mantenga una buena cobertura del código afectado por la tarea. y sobre todo que siga buenas practicas backend

## Documentación y/o indicaciones para el frontend

Por cada issue que se cree, debes generar una documentación o indicaciones claras para el equipo de frontend. Esta documentación debe detallar cómo interactuar con la nueva funcionalidad o corrección implementada, incluyendo ejemplos de uso, endpoints de API, parámetros esperados y cualquier otra información relevante que facilite la integración en el frontend. Asegúrate de que la documentación sea comprensible y esté bien estructurada, permitiendo al equipo de frontend implementar los cambios necesarios sin confusiones. Considera también que estas instrucciones puedan apoyar al agente de IA en el futuro para generar código frontend relacionado con la tarea o codigo implementado.

## Instrucciones para el uso de Github MCP

cuando haces git add -A, git commit -m "mensaje" se traba por lo que usa el MCP Github para hacer commit y push de los cambios. Asegúrate de que el mensaje del commit sea claro y descriptivo, reflejando los cambios realizados. Utiliza el MCP para gestionar los commits y pushes de manera eficiente, evitando conflictos y asegurando que el historial del repositorio se mantenga limpio y organizado. Porque si los haces con terminal se traba y no se pueden subir los cambios. En general todo lo que quieras hacer de github hazlo con MCP, ya que es más eficiente y evita problemas de bloqueo.


## Workflow de Ramas Optimizado

### Estructura de Ramas

El proyecto utiliza un workflow optimizado para equipos pequeños con máxima eficiencia MCP:

```
main/                           # Producción (siempre estable)
dev/                           # Integration & staging 
├── feature/N-frontend-description    # Trabajo frontend
├── feature/N-backend-description     # Trabajo backend
├── feature/N-fullstack-description   # Colaboración frontend+backend
├── fix/N-frontend-description        # Fixes frontend
├── fix/N-backend-description         # Fixes backend
└── docs/N-improvement               # Documentación
```

### Convención de Naming

**OBLIGATORIO:** Todas las ramas deben seguir este patrón:
```
tipo/número-área-descripción

Ejemplos:
- feature/123-frontend-user-dashboard
- feature/124-backend-user-api
- feature/125-fullstack-auth-system
- fix/126-frontend-validation-bug
- fix/127-backend-security-patch
- docs/128-api-documentation
```

### Workflow de Ramas

**Para GitHub Copilot/MCP:**

1. **Crear Issue:** Siempre crear issue primero (obligatorio)
2. **Crear Rama:** Usar convención exacta: `tipo/N-área-descripción`
3. **Desarrollo:** Todo el trabajo en la rama
4. **PR:** SIEMPRE hacer PR hacia `dev` (NUNCA directamente a `main`)
5. **Tests Automáticos:** CI/CD detecta tipo de rama y ejecuta tests apropiados
6. **Merge a dev:** Automático cuando tests pasan y hay aprobación
7. **Integration Testing:** Automático en rama `dev`
8. **Deploy a main:** Manual, solo cuando `dev` esté estable