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
