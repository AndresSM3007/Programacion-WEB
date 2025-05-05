// Lógica para cambiar entre modo oscuro y claro
document.getElementById("modoOscuro").addEventListener("click", () => {
    document.body.classList.toggle("modo-oscuro");
});

// Función de búsqueda
function buscar() {
    const query = document.getElementById("busqueda").value.toLowerCase();
    const preguntas = document.querySelectorAll(".pregunta");
    preguntas.forEach(pregunta => {
        const titulo = pregunta.querySelector("p").textContent.toLowerCase();
        if (titulo.includes(query)) {
            pregunta.style.display = "block";
        } else {
            pregunta.style.display = "none";
        }
    });
}

// Lógica para la paginación
let currentPage = 1;
const questionsPerPage = 5;
let questions = []; // Simula tus preguntas

// Mostrar las preguntas en el foro
function mostrarPreguntas() {
    const startIndex = (currentPage - 1) * questionsPerPage;
    const endIndex = startIndex + questionsPerPage;
    const questionsToShow = questions.slice(startIndex, endIndex);

    const preguntasContainer = document.getElementById("preguntas");
    preguntasContainer.innerHTML = "";
    questionsToShow.forEach(question => {
        const preguntaElement = document.createElement("div");
        preguntaElement.classList.add("pregunta");
        preguntaElement.innerHTML = `
            <p>${question.title}</p>
            <p>${question.description}</p>
            <button onclick="votar(${question.id}, 'up')">👍</button>
            <button onclick="votar(${question.id}, 'down')">👎</button>
        `;
        preguntasContainer.appendChild(preguntaElement);
    });
}

// Paginación
document.getElementById("prev").addEventListener("click", () => {
    if (currentPage > 1) {
        currentPage--;
        mostrarPreguntas();
    }
});

document.getElementById("next").addEventListener("click", () => {
    if (currentPage < questions.length / questionsPerPage) {
        currentPage++;
        mostrarPreguntas();
    }
});

// Función para votar por las preguntas
function votar(questionId, voto) {
    const question = questions.find(q => q.id === questionId);
    if (voto === 'up') {
        question.votes += 1;
    } else if (voto === 'down') {
        question.votes -= 1;
    }
    mostrarPreguntas();
}

// Agregar nueva pregunta al foro
document.getElementById("publicarBtn").addEventListener("click", () => {
    const title = document.getElementById("titulo").value;
    const description = document.getElementById("descripcion").value;
    const category = document.getElementById("categoria").value;

    // Crear una nueva pregunta
    const newQuestion = {
        id: questions.length + 1,
        title,
        description,
        category,
        votes: 0
    };

    // Agregar la nueva pregunta al array de preguntas
    questions.push(newQuestion);

    // Limpiar los campos del formulario
    document.getElementById("titulo").value = "";
    document.getElementById("descripcion").value = "";

    mostrarPreguntas();
});

// Registro de usuario
document.getElementById("registrarBtn").addEventListener("click", () => {
    const username = document.getElementById("username").value;
    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;

    // Aquí iría la lógica para registrar al usuario (ej. almacenar en la base de datos)

    alert("Usuario registrado exitosamente.");
});

// Iniciar sesión
document.getElementById("loginBtn").addEventListener("click", () => {
    const loginEmail = document.getElementById("loginEmail").value;
    const loginPassword = document.getElementById("loginPassword").value;

    // Aquí iría la lógica para verificar las credenciales del usuario (ej. comparación con la base de datos)

    alert("Inicio de sesión exitoso.");
});

// Función para mostrar las preguntas cuando se carga la página
mostrarPreguntas();
document.addEventListener("DOMContentLoaded", function () {
    // REGISTRO
    const registroForm = document.getElementById("registroForm");
    if (registroForm) {
        registroForm.addEventListener("submit", function (event) {
            event.preventDefault();
            let nombre = document.getElementById("nombre").value;
            let correo = document.getElementById("correo").value;
            let password = document.getElementById("password").value;

            let usuarios = JSON.parse(localStorage.getItem("usuarios")) || [];

            if (usuarios.some(user => user.correo === correo)) {
                alert("El correo ya está registrado.");
                return;
            }

            usuarios.push({ nombre, correo, password });
            localStorage.setItem("usuarios", JSON.stringify(usuarios));

            alert("Registro exitoso. Ahora puedes iniciar sesión.");
            window.location.href = "login.html";
        });
    }

    // INICIAR SESIÓN
    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
        loginForm.addEventListener("submit", function (event) {
            event.preventDefault();
            let correo = document.getElementById("correo").value;
            let password = document.getElementById("password").value;

            let usuarios = JSON.parse(localStorage.getItem("usuarios")) || [];
            let usuario = usuarios.find(user => user.correo === correo && user.password === password);

            if (usuario) {
                localStorage.setItem("usuarioActual", JSON.stringify(usuario));
                alert("Inicio de sesión exitoso.");
                window.location.href = "index.html";  // Redirige a la página principal
            } else {
                alert("Correo o contraseña incorrectos.");
            }
        });
    }
});

// Función para cargar preguntas
async function cargarPreguntas(tema = null, orden = 'fecha_creacion DESC') {
    try {
        let url = 'preguntas.php';
        if (tema) {
            url += `?tema=${tema}&orden=${orden}`;
        }
        
        const response = await fetch(url);
        const preguntas = await response.json();
        
        const contenedor = document.getElementById('preguntas-container');
        contenedor.innerHTML = '';
        
        preguntas.forEach(pregunta => {
            const preguntaElement = document.createElement('div');
            preguntaElement.className = 'pregunta card mb-3';
            preguntaElement.innerHTML = `
                <div class="card-body">
                    <h5 class="card-title">${pregunta.titulo}</h5>
                    <p class="card-text">${pregunta.descripcion}</p>
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
                            <span class="badge bg-secondary">${pregunta.votos}</span>
                            <button class="btn btn-sm btn-outline-danger downvote" data-id="${pregunta.id}">↓</button>
                        </div>
                    </div>
                    <div class="respuestas mt-3" id="respuestas-${pregunta.id}" style="display:none;"></div>
                </div>
            `;
            contenedor.appendChild(preguntaElement);
        });
        
        // Agregar eventos a los botones
        document.querySelectorAll('.ver-respuestas').forEach(btn => {
            btn.addEventListener('click', cargarRespuestas);
        });
        
        document.querySelectorAll('.upvote, .downvote').forEach(btn => {
            btn.addEventListener('click', votarPregunta);
        });
    } catch (error) {
        console.error('Error al cargar preguntas:', error);
    }
}

// Función para manejar la subida de imágenes
document.getElementById('imagen').addEventListener('change', function(e) {
    const previewContainer = document.getElementById('preview-container');
    const previewImagen = document.getElementById('preview-imagen');
    
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImagen.src = e.target.result;
            previewContainer.style.display = 'block';
        }
        
        reader.readAsDataURL(this.files[0]);
    }
});

document.getElementById('eliminar-imagen').addEventListener('click', function() {
    document.getElementById('imagen').value = '';
    document.getElementById('preview-container').style.display = 'none';
});

// Función para enviar una nueva pregunta
document.getElementById('formPregunta').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('preguntas.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Pregunta publicada con éxito');
            this.reset();
            document.getElementById('preview-container').style.display = 'none';
            cargarPreguntas();
        } else {
            alert('Error al publicar la pregunta: ' + (result.error || ''));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al enviar la pregunta');
    }
});