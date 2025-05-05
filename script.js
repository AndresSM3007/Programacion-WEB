// =============================================
// FUNCIONES GENERALES
// =============================================

// Modo oscuro/claro
document.getElementById("modoOscuro")?.addEventListener("click", () => {
    document.body.classList.toggle("modo-oscuro");
    localStorage.setItem('modoOscuro', document.body.classList.contains("modo-oscuro"));
});

// Verificar modo oscuro al cargar
if (localStorage.getItem('modoOscuro') === 'true') {
    document.body.classList.add("modo-oscuro");
}

// =============================================
// AUTENTICACIÓN Y USUARIOS
// =============================================

// Verificar sesión al cargar la página
async function verificarSesion() {
    try {
        const response = await fetch('api/verificar_sesion.php', {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            }
        });
        
        if (response.status === 401) {
            if (window.location.pathname !== '/login.php' && window.location.pathname !== '/registro.php') {
                window.location.href = 'login.php';
            }
            return false;
        }
        
        const data = await response.json();
        return data.autenticado;
    } catch (error) {
        console.error('Error al verificar sesión:', error);
        return false;
    }
}

// Cerrar sesión
function cerrarSesion() {
    fetch('api/logout.php', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
        }
    }).finally(() => {
        localStorage.removeItem('auth_token');
        window.location.href = 'login.php';
    });
}

// Asignar evento al botón de cerrar sesión
document.getElementById('logoutBtn')?.addEventListener('click', cerrarSesion);

// =============================================
// MANEJO DE FORMULARIOS
// =============================================

document.addEventListener("DOMContentLoaded", function () {
    // REGISTRO
    const registroForm = document.getElementById("registroForm");
    if (registroForm) {
        registroForm.addEventListener("submit", async function (event) {
            event.preventDefault();
            const formData = new FormData(registroForm);
            
            try {
                const response = await fetch('api/registro.php', {
                    method: 'POST',
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
                console.error('Error:', error);
                mostrarErrorRegistro("Error al conectar con el servidor");
            }
        });
    }

    // INICIAR SESIÓN
    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
        loginForm.addEventListener("submit", async function (event) {
            event.preventDefault();
            const formData = new FormData(loginForm);
            
            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    localStorage.setItem('auth_token', result.token);
                    window.location.href = "index.php";
                } else {
                    mostrarErrorLogin(result.message || "Credenciales incorrectas");
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarErrorLogin("Error al conectar con el servidor");
            }
        });
    }

    // Mostrar errores en formularios
    function mostrarErrorLogin(mensaje) {
        const errorElement = document.getElementById('loginError') || crearElementoError('loginForm', mensaje);
        errorElement.textContent = mensaje;
    }
    
    function mostrarErrorRegistro(mensaje) {
        const errorElement = document.getElementById('registroError') || crearElementoError('registroForm', mensaje);
        errorElement.textContent = mensaje;
    }
    
    function crearElementoError(formId, mensaje) {
        const form = document.getElementById(formId);
        const errorElement = document.createElement('div');
        errorElement.id = `${formId}Error`;
        errorElement.className = 'alert alert-danger';
        errorElement.textContent = mensaje;
        form.prepend(errorElement);
        return errorElement;
    }
});

// =============================================
// PREGUNTAS Y RESPUESTAS
// =============================================

let currentPage = 1;
const questionsPerPage = 5;

// Cargar preguntas
async function cargarPreguntas(tema = null, orden = 'fecha_creacion DESC') {
    if (!await verificarSesion() && window.location.pathname !== '/login.php') {
        return;
    }

    try {
        let url = 'api/preguntas.php';
        const params = new URLSearchParams();
        
        if (tema) params.append('tema', tema);
        params.append('orden', orden);
        params.append('pagina', currentPage);
        params.append('por_pagina', questionsPerPage);
        
        url += `?${params.toString()}`;
        
        const response = await fetch(url, {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            }
        });
        
        if (response.status === 401) {
            window.location.href = 'login.php';
            return;
        }
        
        const data = await response.json();
        mostrarPreguntas(data.preguntas, data.total);
    } catch (error) {
        console.error('Error al cargar preguntas:', error);
        mostrarError('Error al cargar preguntas');
    }
}

// Mostrar preguntas en la interfaz
function mostrarPreguntas(preguntas, totalPreguntas) {
    const contenedor = document.getElementById('preguntas-container');
    if (!contenedor) return;
    
    contenedor.innerHTML = '';
    
    if (preguntas.length === 0) {
        contenedor.innerHTML = '<div class="alert alert-info">No hay preguntas disponibles</div>';
        return;
    }
    
    preguntas.forEach(pregunta => {
        const preguntaElement = document.createElement('div');
        preguntaElement.className = 'pregunta card mb-3';
        preguntaElement.innerHTML = `
            <div class="card-body">
                <h5 class="card-title">${pregunta.titulo}</h5>
                <p class="card-text">${pregunta.descripcion}</p>
                ${pregunta.imagen ? `<img src="uploads/${pregunta.imagen}" class="img-fluid mb-3" alt="Imagen de la pregunta">` : ''}
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Publicado por: ${pregunta.autor}</small>
                    <small class="text-muted">Tema: ${pregunta.tema}</small>
                    <small class="text-muted">${new Date(pregunta.fecha_creacion).toLocaleString()}</small>
                </div>
                <div class="mt-2">
                    <button class="btn btn-sm btn-outline-primary ver-respuestas" data-id="${pregunta.id}">
                        Ver respuestas (${pregunta.respuestas || 0})
                    </button>
                    <div class="votos d-inline-block ms-3">
                        <button class="btn btn-sm btn-outline-success upvote" data-id="${pregunta.id}">↑</button>
                        <span class="badge bg-secondary" data-pregunta="${pregunta.id}">${pregunta.votos}</span>
                        <button class="btn btn-sm btn-outline-danger downvote" data-id="${pregunta.id}">↓</button>
                    </div>
                </div>
                <div class="respuestas mt-3" id="respuestas-${pregunta.id}" style="display:none;"></div>
            </div>
        `;
        contenedor.appendChild(preguntaElement);
    });
    
    // Configurar paginación
    configurarPaginacion(totalPreguntas);
    
    // Asignar eventos
    document.querySelectorAll('.ver-respuestas').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const preguntaId = e.target.getAttribute('data-id');
            cargarRespuestas(preguntaId);
        });
    });
    
    document.querySelectorAll('.upvote').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const preguntaId = e.target.getAttribute('data-id');
            votarPregunta(preguntaId, 'up');
        });
    });
    
    document.querySelectorAll('.downvote').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const preguntaId = e.target.getAttribute('data-id');
            votarPregunta(preguntaId, 'down');
        });
    });
}

// Configurar paginación
function configurarPaginacion(totalPreguntas) {
    const totalPages = Math.ceil(totalPreguntas / questionsPerPage);
    const pagination = document.getElementById('pagination');
    
    if (!pagination) return;
    
    pagination.innerHTML = `
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" id="prevPage">Anterior</a>
        </li>
        <li class="page-item disabled">
            <span class="page-link">Página ${currentPage} de ${totalPages}</span>
        </li>
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" id="nextPage">Siguiente</a>
        </li>
    `;
    
    document.getElementById('prevPage')?.addEventListener('click', (e) => {
        e.preventDefault();
        if (currentPage > 1) {
            currentPage--;
            cargarPreguntas();
        }
    });
    
    document.getElementById('nextPage')?.addEventListener('click', (e) => {
        e.preventDefault();
        if (currentPage < totalPages) {
            currentPage++;
            cargarPreguntas();
        }
    });
}

// Cargar respuestas
async function cargarRespuestas(preguntaId) {
    try {
        const response = await fetch(`api/respuestas.php?pregunta_id=${preguntaId}`, {
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            }
        });
        
        if (response.status === 401) {
            window.location.href = 'login.php';
            return;
        }
        
        const respuestas = await response.json();
        mostrarRespuestas(preguntaId, respuestas);
    } catch (error) {
        console.error('Error al cargar respuestas:', error);
        mostrarError('Error al cargar respuestas');
    }
}

// Mostrar respuestas
function mostrarRespuestas(preguntaId, respuestas) {
    const contenedor = document.getElementById(`respuestas-${preguntaId}`);
    if (!contenedor) return;
    
    // Alternar visibilidad
    if (contenedor.style.display === 'none') {
        contenedor.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
        contenedor.style.display = 'block';
        return;
    }
    
    if (respuestas.length === 0) {
        contenedor.innerHTML = '<div class="alert alert-info">No hay respuestas aún</div>';
        return;
    }
    
    let html = '<h6>Respuestas:</h6>';
    respuestas.forEach(respuesta => {
        html += `
            <div class="respuesta card mb-2">
                <div class="card-body">
                    <p>${respuesta.contenido}</p>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">Respondido por: ${respuesta.autor}</small>
                        <small class="text-muted">${new Date(respuesta.fecha_creacion).toLocaleString()}</small>
                    </div>
                </div>
            </div>
        `;
    });
    
    // Formulario para nueva respuesta
    html += `
        <div class="nueva-respuesta mt-3">
            <textarea class="form-control mb-2" id="respuesta-${preguntaId}" placeholder="Escribe tu respuesta"></textarea>
            <button class="btn btn-primary btn-sm" onclick="enviarRespuesta(${preguntaId})">Enviar respuesta</button>
        </div>
    `;
    
    contenedor.innerHTML = html;
}

// Enviar nueva respuesta
async function enviarRespuesta(preguntaId) {
    const contenido = document.getElementById(`respuesta-${preguntaId}`).value;
    
    if (!contenido.trim()) {
        mostrarError('La respuesta no puede estar vacía');
        return;
    }
    
    try {
        const response = await fetch('api/respuestas.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            },
            body: JSON.stringify({
                pregunta_id: preguntaId,
                contenido: contenido
            })
        });
        
        if (response.status === 401) {
            window.location.href = 'login.php';
            return;
        }
        
        const result = await response.json();
        
        if (result.success) {
            cargarRespuestas(preguntaId);
            document.getElementById(`respuesta-${preguntaId}`).value = '';
        } else {
            mostrarError(result.message || 'Error al enviar respuesta');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al enviar respuesta');
    }
}

// Votar pregunta
async function votarPregunta(preguntaId, tipoVoto) {
    try {
        const response = await fetch('api/votos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            },
            body: JSON.stringify({
                pregunta_id: preguntaId,
                tipo_voto: tipoVoto
            })
        });
        
        if (response.status === 401) {
            window.location.href = 'login.php';
            return;
        }
        
        const result = await response.json();
        
        if (result.success) {
            // Actualizar contador de votos
            const badge = document.querySelector(`.votos span[data-pregunta="${preguntaId}"]`);
            if (badge) {
                badge.textContent = result.nuevos_votos;
            }
        } else {
            mostrarError(result.message || 'Error al votar');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al conectar con el servidor');
    }
}

// =============================================
// FORMULARIO DE NUEVA PREGUNTA
// =============================================

// Vista previa de imagen
document.getElementById('imagen')?.addEventListener('change', function(e) {
    const previewContainer = document.getElementById('preview-container');
    const previewImagen = document.getElementById('preview-imagen');
    
    if (this.files && this.files[0]) {
        // Validar tamaño (máximo 2MB)
        if (this.files[0].size > 2 * 1024 * 1024) {
            mostrarError('La imagen no puede superar los 2MB');
            this.value = '';
            return;
        }
        
        // Validar tipo de archivo
        const tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif'];
        if (!tiposPermitidos.includes(this.files[0].type)) {
            mostrarError('Solo se permiten imágenes JPEG, PNG o GIF');
            this.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImagen.src = e.target.result;
            previewContainer.style.display = 'block';
        };
        reader.readAsDataURL(this.files[0]);
    }
});

// Eliminar imagen seleccionada
document.getElementById('eliminar-imagen')?.addEventListener('click', function() {
    document.getElementById('imagen').value = '';
    document.getElementById('preview-container').style.display = 'none';
});

// Enviar nueva pregunta
document.getElementById('formPregunta')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const titulo = document.getElementById('titulo').value;
    const descripcion = document.getElementById('descripcion').value;
    const tema = document.getElementById('tema').value;
    const imagen = document.getElementById('imagen').files[0];
    
    if (!titulo.trim() || !descripcion.trim()) {
        mostrarError('El título y la descripción son obligatorios');
        return;
    }
    
    const formData = new FormData();
    formData.append('titulo', titulo);
    formData.append('descripcion', descripcion);
    formData.append('tema', tema);
    if (imagen) formData.append('imagen', imagen);
    
    try {
        const response = await fetch('api/preguntas.php', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
            },
            body: formData
        });
        
        if (response.status === 401) {
            window.location.href = 'login.php';
            return;
        }
        
        const result = await response.json();
        
        if (result.success) {
            // Limpiar formulario
            this.reset();
            document.getElementById('preview-container').style.display = 'none';
            
            // Recargar preguntas
            currentPage = 1;
            cargarPreguntas();
            
            // Mostrar mensaje de éxito
            mostrarExito('Pregunta publicada correctamente');
        } else {
            mostrarError(result.message || 'Error al publicar la pregunta');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al conectar con el servidor');
    }
});

// =============================================
// FUNCIONES AUXILIARES
// =============================================

function mostrarError(mensaje) {
    const errorElement = document.getElementById('error-message') || crearElementoMensaje('error-message', 'alert-danger');
    errorElement.textContent = mensaje;
    errorElement.style.display = 'block';
    
    setTimeout(() => {
        errorElement.style.display = 'none';
    }, 5000);
}

function mostrarExito(mensaje) {
    const exitoElement = document.getElementById('success-message') || crearElementoMensaje('success-message', 'alert-success');
    exitoElement.textContent = mensaje;
    exitoElement.style.display = 'block';
    
    setTimeout(() => {
        exitoElement.style.display = 'none';
    }, 5000);
}

function crearElementoMensaje(id, clase) {
    const elemento = document.createElement('div');
    elemento.id = id;
    elemento.className = `alert ${clase}`;
    elemento.style.display = 'none';
    elemento.style.position = 'fixed';
    elemento.style.top = '20px';
    elemento.style.right = '20px';
    elemento.style.zIndex = '1000';
    document.body.appendChild(elemento);
    return elemento;
}

// =============================================
// INICIALIZACIÓN
// =============================================

document.addEventListener("DOMContentLoaded", function() {
    // Cargar preguntas si estamos en la página principal
    if (document.getElementById('preguntas-container')) {
        verificarSesion().then(autenticado => {
            if (autenticado) {
                cargarPreguntas();
            }
        });
    }
    
    // Configurar filtros
    document.getElementById('filtro-tema')?.addEventListener('change', (e) => {
        currentPage = 1;
        cargarPreguntas(e.target.value);
    });
    
    document.getElementById('filtro-orden')?.addEventListener('change', (e) => {
        cargarPreguntas(null, e.target.value);
    });
});

// Ejemplo con fetch API
document.getElementById('form-registro').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = {
        nombre: document.getElementById('nombre').value,
        email: document.getElementById('email').value,
        password: document.getElementById('password').value
    };
    
    try {
        const response = await fetch('https://apoya-tec.webcindario.com/api/auth/register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // Registro exitoso, redirigir al usuario
            window.location.href = '/perfil.html';
        } else {
            // Mostrar error
            alert(data.error || 'Error en el registro');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al conectar con el servidor');
    }
});