// =============================================
// FUNCIONES GENERALES
// =============================================

// Modo oscuro/claro
document.getElementById("modoOscuro")?.addEventListener("click", () => {
    document.body.classList.toggle("modo-oscuro");
    localStorage.setItem("modoOscuro", document.body.classList.contains("modo-oscuro"));
});

// Verificar modo oscuro al cargar
if (localStorage.getItem("modoOscuro") === "true") {
    document.body.classList.add("modo-oscuro");
}

// =============================================
// AUTENTICACIÓN Y USUARIOS
// =============================================

// Verificar sesión al cargar la página
async function verificarSesion() {
    try {
        const response = await fetch("api/verificar_sesion.php", {
            headers: {
                "Authorization": "Bearer " + localStorage.getItem("auth_token")
            }
        });

        if (response.status === 401) {
            // Si no está autenticado y no está en login/registro, redirigir
            if (!["/login.php", "/registro.php"].includes(window.location.pathname)) {
                 window.location.href = "login.php";
            }
            return { autenticado: false }; // Devolver objeto
        }

        const data = await response.json();
        return data; // Devolver { autenticado: true, usuario: { id: ..., nombre: ... } }
    } catch (error) {
        console.error("Error al verificar sesión:", error);
        return { autenticado: false };
    }
}

// Cerrar sesión (CORREGIDO para evitar error de sintaxis en línea 58)
function cerrarSesion() {
    fetch("api/logout.php", {
        method: "POST",
        headers: {
            "Authorization": "Bearer " + localStorage.getItem("auth_token")
        }
    }).finally(() => {
        localStorage.removeItem("auth_token");
        // Limpiar info de usuario si se muestra
        const userInfo = document.getElementById("user-info");
        if (userInfo) userInfo.style.display = "none";
        
        // Mostrar botones de login/registro (usando comprobación explícita)
        const loginBtnElem = document.getElementById("loginBtn");
        if (loginBtnElem) loginBtnElem.style.display = "inline-block";
        const registerBtnElem = document.getElementById("registerBtn");
        if (registerBtnElem) registerBtnElem.style.display = "inline-block";
        const logoutBtnElem = document.getElementById("logoutBtn");
        if (logoutBtnElem) logoutBtnElem.style.display = "none"; // Línea 58 equivalente
        
        // Ocultar formulario de nueva pregunta si existe
        const nuevaPreguntaFormElem = document.getElementById("nueva-pregunta-form-container");
        if (nuevaPreguntaFormElem) nuevaPreguntaFormElem.style.display = "none";
        
        // Recargar o redirigir si es necesario
        if (!["/login.php", "/registro.php"].includes(window.location.pathname)) {
             window.location.href = "login.php";
        }
    });
}

// Asignar evento al botón de cerrar sesión
const logoutBtnListener = document.getElementById("logoutBtn");
if (logoutBtnListener) {
    logoutBtnListener.addEventListener("click", cerrarSesion);
}

// Función para actualizar UI basada en estado de autenticación
async function actualizarUIAutenticacion() {
    const estadoSesion = await verificarSesion();
    const loginBtn = document.getElementById("loginBtn");
    const registerBtn = document.getElementById("registerBtn");
    const logoutBtn = document.getElementById("logoutBtn");
    const userInfo = document.getElementById("user-info");
    const nuevaPreguntaForm = document.getElementById("nueva-pregunta-form-container");

    if (estadoSesion.autenticado) {
        if (loginBtn) loginBtn.style.display = "none";
        if (registerBtn) registerBtn.style.display = "none";
        if (logoutBtn) logoutBtn.style.display = "inline-block";
        if (userInfo && estadoSesion.usuario) {
            userInfo.textContent = `Bienvenido, ${escapeHTML(estadoSesion.usuario.nombre)}`;
            userInfo.style.display = "inline";
        }
        if (nuevaPreguntaForm) nuevaPreguntaForm.style.display = "block";
    } else {
        if (loginBtn) loginBtn.style.display = "inline-block";
        if (registerBtn) registerBtn.style.display = "inline-block";
        if (logoutBtn) logoutBtn.style.display = "none";
        if (userInfo) userInfo.style.display = "none";
        if (nuevaPreguntaForm) nuevaPreguntaForm.style.display = "none";
    }
}

// =============================================
// MANEJO DE FORMULARIOS (Login/Registro)
// =============================================

document.addEventListener("DOMContentLoaded", function () {
    // Solo ejecutar lógica de autenticación y foro si no estamos en login/registro
    if (!["/login.php", "/registro.php"].includes(window.location.pathname)) {
        actualizarUIAutenticacion();
        // Cargar preguntas iniciales si estamos en index.html
        if (window.location.pathname.endsWith("/") || window.location.pathname.endsWith("index.html")) {
             cargarPreguntas();
             configurarFiltrosYBusqueda(); // Configurar listeners después de cargar el DOM
        }
    }

    // REGISTRO
    const registroForm = document.getElementById("registroForm");
    if (registroForm) {
        registroForm.addEventListener("submit", async function (event) {
            event.preventDefault();
            const formData = new FormData(registroForm);

            try {
                const response = await fetch("api/registro.php", {
                    method: "POST",
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert("Registro exitoso. Ahora puedes iniciar sesión.");
                    window.location.href = "login.php";
                } else {
                    mostrarErrorRegistro(result.message || "Error en el registro");
                }
            } catch (error) {
                console.error("Error:", error);
                mostrarErrorRegistro("Error al conectar con el servidor");
            }
        });
    }

    // INICIAR SESIÓN
    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
        loginForm.addEventListener("submit", async function (event) {
            event.preventDefault(); // Asegura que JS maneje el envío
            const formData = new FormData(loginForm);

            try {
                const response = await fetch("api/login.php", {
                    method: "POST",
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    localStorage.setItem("auth_token", result.token);
                    window.location.href = "index.html"; // Redirigir a la página principal
                } else {
                    mostrarErrorLogin(result.message || "Credenciales incorrectas");
                }
            } catch (error) {
                console.error("Error:", error);
                mostrarErrorLogin("Error al conectar con el servidor");
            }
        });
    }

    // Mostrar errores en formularios
    function mostrarErrorLogin(mensaje) {
        let errorElement = document.getElementById("loginError");
        if (!errorElement) {
            errorElement = document.createElement("div");
            errorElement.id = "loginError";
            errorElement.className = "alert alert-danger mt-3";
            loginForm?.prepend(errorElement); // Usar prepend para mostrar dentro del form
        }
        errorElement.textContent = mensaje;
        errorElement.style.display = "block";
    }

    function mostrarErrorRegistro(mensaje) {
         let errorElement = document.getElementById("registroError");
        if (!errorElement) {
            errorElement = document.createElement("div");
            errorElement.id = "registroError";
            errorElement.className = "alert alert-danger mt-3";
            registroForm?.prepend(errorElement);
        }
        errorElement.textContent = mensaje;
        errorElement.style.display = "block";
    }
});

// =============================================
// PREGUNTAS Y RESPUESTAS
// =============================================

let currentPage = 1;
const questionsPerPage = 5;

// Cargar preguntas
async function cargarPreguntas(tema = null, orden = "p.fecha_creacion DESC", busqueda = null) {
    // const estadoSesion = await verificarSesion(); // No longer needed to check auth for loading
    // if (!estadoSesion.autenticado && !["/login.php", "/registro.php"].includes(window.location.pathname)) {
    //     // Si no está autenticado y no está en login/registro, no cargar
    //     return;
    // }

    const contenedor = document.getElementById("preguntas-container");
    if (!contenedor) return;
    contenedor.innerHTML = 
        '<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i><p>Cargando preguntas...</p></div>';

    try {
        let url = "api/preguntas.php";
        const params = new URLSearchParams();

        if (tema) params.append("tema", tema);
        if (busqueda) params.append("busqueda", busqueda);
        params.append("orden", orden);
        params.append("pagina", currentPage);
        params.append("por_pagina", questionsPerPage);

        url += `?${params.toString()}`;

        const headers = {};
        const token = localStorage.getItem("auth_token");
        if (token) {
            headers["Authorization"] = "Bearer " + token;
        }

        const response = await fetch(url, { headers });

        if (response.status === 401) {
            cerrarSesion();
            return;
        }

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        mostrarPreguntas(data.preguntas, data.total);
    } catch (error) {
        console.error("Error al cargar preguntas:", error);
        if (contenedor) contenedor.innerHTML = 
            '<div class="alert alert-danger">Error al cargar preguntas. Inténtalo de nuevo más tarde.</div>';
        mostrarError("Error al cargar preguntas");
    }
}

async function mostrarPreguntas(preguntas, totalPreguntas) {
    const estadoSesion = await verificarSesion(); // Check auth status for images
    const contenedor = document.getElementById("preguntas-container");
    if (!contenedor) return;

    contenedor.innerHTML = "";

    if (!preguntas || preguntas.length === 0) {
        contenedor.innerHTML = 
            '<div class="alert alert-info">No se encontraron preguntas que coincidan con los filtros.</div>';
        configurarPaginacion(0);
        return;
    }

    preguntas.forEach(pregunta => {
        const preguntaElement = document.createElement("div");
        preguntaElement.className = "pregunta card mb-3";
        const imagenHTML = (estadoSesion.autenticado && pregunta.imagen_nombre) ? 
            `<img src="api/ver_imagen.php?nombre=${pregunta.imagen_nombre}" class="img-fluid mb-3 rounded" alt="Imagen de la pregunta" style="max-height: 300px; object-fit: contain;">` : 
            (pregunta.imagen_nombre ? '<p class="text-muted fst-italic">(Imagen disponible para usuarios registrados)</p>' : '');
        preguntaElement.innerHTML = `
            <div class="card-body">
                <h5 class="card-title">${escapeHTML(pregunta.titulo)}</h5>
                <p class="card-text">${escapeHTML(pregunta.descripcion)}</p>
                ${imagenHTML}
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <small class="text-muted me-3">Publicado por: ${escapeHTML(pregunta.autor)}</small>
                    <small class="text-muted me-3">Tema: ${escapeHTML(pregunta.tema)}</small>
                    <small class="text-muted">${new Date(pregunta.fecha_creacion).toLocaleString()}</small>
                </div>
                <div class="mt-2 d-flex justify-content-between align-items-center">
                    <button class="btn btn-sm btn-outline-primary ver-respuestas" data-id="${pregunta.id}">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" style="display: none;"></span>
                        Ver/Ocultar respuestas (${pregunta.num_respuestas || 0})
                    </button>
                    <div class="votos d-inline-block ms-3">
                        <button class="btn btn-sm btn-outline-success upvote" data-id="${pregunta.id}" title="Votar positivo">?</button>
                        <span class="badge bg-secondary mx-1" data-pregunta="${pregunta.id}">${pregunta.votos}</span>
                        <button class="btn btn-sm btn-outline-danger downvote" data-id="${pregunta.id}" title="Votar negativo">?</button>
                    </div>
                </div>
                <div class="respuestas mt-3 border-top pt-3" id="respuestas-${pregunta.id}" style="display:none;"></div>
            </div>
        `;
        contenedor.appendChild(preguntaElement);
    });

    configurarPaginacion(totalPreguntas);

    contenedor.addEventListener("click", (e) => {
        const target = e.target;
        if (target.classList.contains("ver-respuestas")) {
            const boton = target;
            const preguntaId = boton.getAttribute("data-id");
            const respuestasDiv = document.getElementById(`respuestas-${preguntaId}`);
            const spinner = boton.querySelector(".spinner-border");

            if (respuestasDiv) {
                if (respuestasDiv.style.display === "none") {
                    if(spinner) spinner.style.display = "inline-block";
                    boton.disabled = true;
                    cargarRespuestas(preguntaId).finally(() => {
                         if(spinner) spinner.style.display = "none";
                         boton.disabled = false;
                         respuestasDiv.style.display = "block";
                    });
                } else {
                    respuestasDiv.style.display = "none";
                    respuestasDiv.innerHTML = "";
                }
            }
        } else if (target.classList.contains("upvote")) {
            const preguntaId = target.getAttribute("data-id");
            votarPregunta(preguntaId, "up");
        } else if (target.classList.contains("downvote")) {
            const preguntaId = target.getAttribute("data-id");
            votarPregunta(preguntaId, "down");
        }
    });
}

// Configurar paginación
function configurarPaginacion(totalPreguntas) {
    const totalPages = Math.ceil(totalPreguntas / questionsPerPage);
    const pagination = document.getElementById("pagination");

    if (!pagination) return;
    pagination.innerHTML = "";

    if (totalPages <= 1) return;

    const prevLi = document.createElement("li");
    prevLi.className = `page-item ${currentPage === 1 ? "disabled" : ""}`;
    prevLi.innerHTML = `<a class="page-link" href="#" id="prevPage">Anterior</a>`;
    pagination.appendChild(prevLi);

    const currentLi = document.createElement("li");
    currentLi.className = "page-item disabled";
    currentLi.innerHTML = `<span class="page-link">Página ${currentPage} de ${totalPages}</span>`;
    pagination.appendChild(currentLi);

    const nextLi = document.createElement("li");
    nextLi.className = `page-item ${currentPage === totalPages ? "disabled" : ""}`;
    nextLi.innerHTML = `<a class="page-link" href="#" id="nextPage">Siguiente</a>`;
    pagination.appendChild(nextLi);

    const prevPageLink = document.getElementById("prevPage");
    if (prevPageLink) {
        prevPageLink.addEventListener("click", (e) => {
            e.preventDefault();
            if (currentPage > 1) {
                currentPage--;
                recargarPreguntasConFiltros();
                window.scrollTo(0, document.getElementById("foro")?.offsetTop || 0);
            }
        });
    }

    const nextPageLink = document.getElementById("nextPage");
    if (nextPageLink) {
        nextPageLink.addEventListener("click", (e) => {
            e.preventDefault();
            if (currentPage < totalPages) {
                currentPage++;
                recargarPreguntasConFiltros();
                window.scrollTo(0, document.getElementById("foro")?.offsetTop || 0);
            }
        });
    }
}

// Cargar respuestas
async function cargarRespuestas(preguntaId) {
    const contenedor = document.getElementById(`respuestas-${preguntaId}`);
    if (!contenedor) return;

    contenedor.innerHTML = 
        '<div class="text-center my-3"><i class="fas fa-spinner fa-spin"></i> Cargando respuestas...</div>';
    contenedor.style.display = "block";

    try {
        const headers = {};
        const token = localStorage.getItem("auth_token");
        if (token) {
            headers["Authorization"] = "Bearer " + token;
        }

        const response = await fetch(`api/respuestas.php?pregunta_id=${preguntaId}`, { headers });

        if (response.status === 401) {
            cerrarSesion();
            return;
        }
        if (!response.ok) {
             throw new Error(`HTTP error! status: ${response.status}`);
        }

        const respuestas = await response.json();
        mostrarRespuestas(preguntaId, respuestas);
    } catch (error) {
        console.error("Error al cargar respuestas:", error);
        if (contenedor) contenedor.innerHTML = 
            '<div class="alert alert-warning">Error al cargar respuestas.</div>';
        mostrarError("Error al cargar respuestas");
    }
}

async function mostrarRespuestas(preguntaId, respuestas) {
    const estadoSesion = await verificarSesion(); // Check auth status for images
    const contenedor = document.getElementById(`respuestas-${preguntaId}`);
    if (!contenedor) return;

    contenedor.innerHTML = "";

    let html = "<h6>Respuestas:</h6>";
    if (!respuestas || respuestas.length === 0) {
        html += 
            '<div class="alert alert-light border-start border-4 border-info ps-3">Todavía no hay respuestas para esta pregunta. ¡Sé el primero en responder!</div>';
    } else {
        respuestas.forEach(respuesta => {
            const imagenRespuestaHTML = (estadoSesion.autenticado && respuesta.imagen_nombre) ? 
                `<img src="api/ver_imagen.php?nombre=${respuesta.imagen_nombre}" class="img-fluid my-2 rounded" alt="Imagen de la respuesta" style="max-height: 200px; object-fit: contain;">` : 
                (respuesta.imagen_nombre ? 
                    (estadoSesion.autenticado ? '' : 
                        '<p class="text-muted fst-italic">(Imagen disponible para usuarios registrados)</p>') : 
                    ''); // Add check for authenticated state before showing image
            html += `
                <div class="respuesta card mb-2 bg-light">
                    <div class="card-body p-2">
                        <p class="card-text mb-1">${escapeHTML(respuesta.contenido)}</p>
                        ${imagenRespuestaHTML}
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <small class="text-muted">Respondido por: ${escapeHTML(respuesta.autor)}</small>
                            <small class="text-muted">${new Date(respuesta.fecha_creacion).toLocaleString()}</small>
                        </div>
                    </div>
                </div>
            `;
        });
    }

    html += `
        <div class="nueva-respuesta mt-3 pt-3 border-top">
            <form id="formRespuesta-${preguntaId}" enctype="multipart/form-data">
                <input type="hidden" name="pregunta_id" value="${preguntaId}">
                <div class="mb-2">
                    <label for="contenido-respuesta-${preguntaId}" class="form-label visually-hidden">Tu respuesta:</label>
                    <textarea class="form-control form-control-sm" id="contenido-respuesta-${preguntaId}" name="contenido" rows="2" placeholder="Escribe tu respuesta aquí..." required></textarea>
                </div>
                <div class="mb-2">
                     <label for="imagen-respuesta-${preguntaId}" class="form-label">Adjuntar imagen (opcional, max 2MB):</label>
                     <input class="form-control form-control-sm" type="file" id="imagen-respuesta-${preguntaId}" name="imagen_respuesta" accept="image/jpeg, image/png, image/gif">
                     <div id="preview-respuesta-${preguntaId}" class="mt-1" style="display:none;">
                         <img id="preview-imagen-respuesta-${preguntaId}" src="#" alt="Vista previa" style="max-width: 100px; max-height: 100px; margin-right: 5px;">
                         <button type="button" class="btn btn-sm btn-outline-danger eliminar-imagen-respuesta" data-input-id="imagen-respuesta-${preguntaId}" data-preview-id="preview-respuesta-${preguntaId}">X</button>
                     </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Enviar respuesta</button>
                <span class="spinner-border spinner-border-sm ms-2" id="spinner-respuesta-${preguntaId}" role="status" aria-hidden="true" style="display: none;"></span>
            </form>
        </div>
    `;

    contenedor.innerHTML = html;

    const formRespuesta = document.getElementById(`formRespuesta-${preguntaId}`);
    if (formRespuesta) {
        formRespuesta.addEventListener("submit", (e) => {
            e.preventDefault();
            enviarRespuesta(preguntaId);
        });

        const inputFileRespuesta = document.getElementById(`imagen-respuesta-${preguntaId}`);
        const previewContainerRespuesta = document.getElementById(`preview-respuesta-${preguntaId}`);
        const previewImageRespuesta = document.getElementById(`preview-imagen-respuesta-${preguntaId}`);

        if (inputFileRespuesta && previewContainerRespuesta && previewImageRespuesta) {
            inputFileRespuesta.addEventListener("change", function() {
                if (this.files && this.files[0]) {
                    if (!validarImagen(this.files[0])) {
                        this.value = "";
                        previewContainerRespuesta.style.display = "none";
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImageRespuesta.src = e.target.result;
                        previewContainerRespuesta.style.display = "block";
                    }
                    reader.readAsDataURL(this.files[0]);
                } else {
                     previewContainerRespuesta.style.display = "none";
                }
            });
        }

        const btnEliminar = previewContainerRespuesta?.querySelector(".eliminar-imagen-respuesta");
        if (btnEliminar) {
            btnEliminar.addEventListener("click", function() {
                const inputId = this.getAttribute("data-input-id");
                const previewId = this.getAttribute("data-preview-id");
                const inputElement = document.getElementById(inputId);
                const previewElement = document.getElementById(previewId);
                if (inputElement) inputElement.value = "";
                if (previewElement) previewElement.style.display = "none";
            });
        }
    }
}

// Enviar nueva respuesta
async function enviarRespuesta(preguntaId) {
    const form = document.getElementById(`formRespuesta-${preguntaId}`);
    if (!form) return;

    const contenidoInput = form.querySelector(`textarea[name="contenido"]`);
    const imagenInput = form.querySelector(`input[name="imagen_respuesta"]`);
    const submitButton = form.querySelector("button[type=\'submit\']");
    const spinner = document.getElementById(`spinner-respuesta-${preguntaId}`);

    if (!contenidoInput || !imagenInput) return;

    if (!contenidoInput.value.trim()) {
        mostrarError("La respuesta no puede estar vacía");
        contenidoInput.focus();
        return;
    }

    if (imagenInput.files.length > 0 && !validarImagen(imagenInput.files[0])) {
        mostrarError("Archivo de imagen inválido (revise tipo o tamaño).");
        imagenInput.focus();
        return;
    }

    const formData = new FormData(form);

    if(spinner) spinner.style.display = "inline-block";
    if(submitButton) submitButton.disabled = true;

    try {
        const response = await fetch("api/respuestas.php", {
            method: "POST",
            headers: {
                "Authorization": "Bearer " + localStorage.getItem("auth_token")
            },
            body: formData
        });

        if (response.status === 401) {
            cerrarSesion();
            return;
        }

        const result = await response.json();

        if (result.success) {
            mostrarExito("Respuesta enviada correctamente.");
            contenidoInput.value = "";
            imagenInput.value = "";
            const previewContainer = document.getElementById(`preview-respuesta-${preguntaId}`);
            if (previewContainer) previewContainer.style.display = "none";
            cargarRespuestas(preguntaId);
        } else {
            mostrarError(result.message || "Error al enviar respuesta");
        }
    } catch (error) {
        console.error("Error:", error);
        mostrarError("Error de conexión al enviar respuesta");
    } finally {
        if(spinner) spinner.style.display = "none";
        if(submitButton) submitButton.disabled = false;
    }
}

// Votar pregunta
async function votarPregunta(preguntaId, tipoVoto) {
    try {
        const response = await fetch("api/votos.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Authorization": "Bearer " + localStorage.getItem("auth_token")
            },
            body: JSON.stringify({
                pregunta_id: preguntaId,
                tipo_voto: tipoVoto // Should be 'up' or 'down'
            })
        });

        // Attempt to parse JSON regardless of status for error messages
        let result;
        try {
            result = await response.json();
        } catch (jsonError) {
            // If JSON parsing fails, use a generic message with status
            if (!response.ok) {
                 throw new Error(`HTTP error! status: ${response.status} - No se pudo obtener detalle del error.`);
            }
            // If response was ok but JSON failed (unlikely here), rethrow
            throw jsonError;
        }

        if (response.status === 401) {
            mostrarError(result.message || "Debes iniciar sesión para votar.");
            return;
        }
        if (response.status === 403) {
            mostrarError(result.message || "No puedes votar.");
            return;
        }

        // Handle other non-ok responses using the parsed message
        if (!response.ok) {
             throw new Error(result.message || `Error HTTP ${response.status}`);
        }

        // Handle successful response
        if (result.success) {
            const badge = document.querySelector(`.votos span[data-pregunta="${preguntaId}"]`);
            if (badge) {
                badge.textContent = result.nuevos_votos;
            }
        } else {
            // Show specific error message from backend if success is false
            mostrarError(result.message || "Error al registrar el voto.");
        }

    } catch (error) {
        console.error("Error al votar:", error);
        // Display the error message caught, which might be the specific one from backend
        mostrarError(error.message || "Error de conexión o procesamiento al votar.");
    }
}

// =============================================
// FORMULARIO DE NUEVA PREGUNTA
// =============================================

const formPregunta = document.getElementById("formPregunta");
if (formPregunta) {
    formPregunta.addEventListener("submit", async function(e) {
        e.preventDefault();

        const tituloInput = document.getElementById("titulo");
        const descripcionInput = document.getElementById("descripcion");
        const imagenInput = document.getElementById("imagen");
        const submitButton = formPregunta.querySelector("button[type=\'submit\']");

        if (!tituloInput || !descripcionInput || !imagenInput || !submitButton) return;

        if (imagenInput.files.length > 0 && !validarImagen(imagenInput.files[0])) {
            mostrarError("Archivo de imagen inválido (revise tipo o tamaño).");
            imagenInput.focus();
            return;
        }

        const formData = new FormData(formPregunta);
        submitButton.disabled = true;
        submitButton.innerHTML = 
            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Publicando...';

        try {
            const response = await fetch("api/preguntas.php", {
                method: "POST",
                headers: {
                    "Authorization": "Bearer " + localStorage.getItem("auth_token")
                },
                body: formData
            });

            if (response.status === 401) {
                cerrarSesion();
                return;
            }

            const result = await response.json();

            if (result.success) {
                mostrarExito("Pregunta publicada exitosamente.");
                formPregunta.reset();
                const previewContainer = document.getElementById("preview-container");
                if(previewContainer) previewContainer.style.display = "none";
                currentPage = 1;
                recargarPreguntasConFiltros();
            } else {
                mostrarError(result.message || "Error al publicar la pregunta.");
            }
        } catch (error) {
            console.error("Error al publicar pregunta:", error);
            mostrarError("Error de conexión al publicar la pregunta.");
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = "Publicar Pregunta";
        }
    });
}

// Vista previa de imagen para NUEVA PREGUNTA
const inputFilePregunta = document.getElementById("imagen");
const previewContainerPregunta = document.getElementById("preview-container");
const previewImagenPregunta = document.getElementById("preview-imagen");
const eliminarBtnPregunta = document.getElementById("eliminar-imagen");

if (inputFilePregunta && previewContainerPregunta && previewImagenPregunta) {
    inputFilePregunta.addEventListener("change", function(e) {
        if (this.files && this.files[0]) {
            if (!validarImagen(this.files[0])) {
                 this.value = "";
                 previewContainerPregunta.style.display = "none";
                 return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImagenPregunta.src = e.target.result;
                previewContainerPregunta.style.display = "flex";
            }
            reader.readAsDataURL(this.files[0]);
        } else {
            previewContainerPregunta.style.display = "none";
        }
    });
}

if (eliminarBtnPregunta && inputFilePregunta && previewContainerPregunta) {
    eliminarBtnPregunta.addEventListener("click", function() {
        inputFilePregunta.value = "";
        previewContainerPregunta.style.display = "none";
    });
}

// =============================================
// FILTROS, BÚSQUEDA Y UTILIDADES
// =============================================

function configurarFiltrosYBusqueda() {
    const filtroTema = document.getElementById("filtro-tema");
    const filtroOrden = document.getElementById("filtro-orden");
    const searchForm = document.getElementById("searchForm");
    const searchInput = document.getElementById("searchInput");

    if (filtroTema) {
        filtroTema.addEventListener("change", () => {
            currentPage = 1;
            recargarPreguntasConFiltros();
        });
    }

    if (filtroOrden) {
        filtroOrden.addEventListener("change", () => {
            currentPage = 1;
            recargarPreguntasConFiltros();
        });
    }

    if (searchForm) {
        searchForm.addEventListener("submit", (e) => {
            e.preventDefault();
            currentPage = 1;
            recargarPreguntasConFiltros();
        });
    }

    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener("input", () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                currentPage = 1;
                recargarPreguntasConFiltros();
            }, 500);
        });
    }
}

function recargarPreguntasConFiltros() {
    const tema = document.getElementById("filtro-tema")?.value || null;
    const orden = document.getElementById("filtro-orden")?.value || "p.fecha_creacion DESC";
    const busqueda = document.getElementById("searchInput")?.value || null;
    cargarPreguntas(tema, orden, busqueda);
}

function escapeHTML(str) {
    if (str === null || str === undefined) return "";
    const div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
}

function validarImagen(file) {
    const tiposPermitidos = ["image/jpeg", "image/png", "image/gif"];
    const tamanoMaximo = 2 * 1024 * 1024; // 2MB

    if (!tiposPermitidos.includes(file.type)) {
        mostrarError("Tipo de archivo de imagen no permitido (solo JPG, PNG, GIF).");
        return false;
    }
    if (file.size > tamanoMaximo) {
        mostrarError("La imagen excede el tamaño máximo permitido (2MB).");
        return false;
    }
    return true;
}

// =============================================
// MENSAJES DE FEEDBACK (Éxito/Error)
// =============================================

function mostrarMensaje(idElemento, mensaje, tipo = "success") {
    const elemento = document.getElementById(idElemento);
    if (!elemento) {
        console.warn(`Elemento de mensaje no encontrado: ${idElemento}`);
        // Crear elemento si no existe?
        return;
    }

    elemento.textContent = mensaje;
    elemento.className = `alert alert-${tipo}`;
    elemento.style.display = "block";
    elemento.style.position = "fixed";
    elemento.style.top = "20px";
    elemento.style.right = "20px";
    elemento.style.zIndex = "1050";

    setTimeout(() => {
        elemento.style.display = "none";
    }, 5000);
}

function mostrarExito(mensaje) {
    mostrarMensaje("success-message", mensaje, "success");
}

function mostrarError(mensaje) {
    mostrarMensaje("error-message", mensaje, "danger");
}

