// Capturar elementos del HTML
const libroForm = document.getElementById('libro-form');
const booksGrid = document.getElementById('books-grid');

// Cargar los libros guardados al abrir la página
document.addEventListener('DOMContentLoaded', mostrarLibros);

// --- EVENTO: GUARDAR NUEVO LIBRO ---
libroForm.addEventListener('submit', (e) => {
    e.preventDefault();

    const titulo = document.getElementById('titulo').value;
    const autor = document.getElementById('autor').value;
    const paginas_totales = parseInt(document.getElementById('paginas').value);
    const estado = document.getElementById('estado').value;

    // Si empieza como Terminado, la página actual es igual al total
    const pagina_actual = (estado === 'Terminado') ? paginas_totales : 0;

    const nuevoLibro = {
        id: Date.now(), // ID único basado en el tiempo
        titulo,
        autor,
        paginas_totales,
        pagina_actual,
        estado
    };

    // Obtener lista actual, sumar el nuevo y guardar
    const libros = obtenerLibrosDeAlmacenamiento();
    libros.push(nuevoLibro);
    guardarLibrosEnAlmacenamiento(libros);

    // Resetear formulario y actualizar pantalla
    libroForm.reset();
    mostrarLibros();
});

// --- FUNCIÓN: RENDERIZAR TARJETAS EN PANTALLA ---
function mostrarLibros() {
    booksGrid.innerHTML = '';
    const libros = obtenerLibrosDeAlmacenamiento();

    if (libros.length === 0) {
        booksGrid.innerHTML = '<p style="color: #777; grid-column: 1/-1;">No hay libros guardados todavía.</p>';
        return;
    }

    libros.forEach(libro => {
        const card = document.createElement('div');
        card.className = `book-card status-${libro.estado.toLowerCase()}`;
        
        card.innerHTML = `
            <div class="book-info">
                <h3>${libro.titulo}</h3>
                <p class="author">${libro.autor}</p>
                <span class="badge">${libro.estado}</span>
                
                <div class="progress-form" style="margin: 15px 0 10px 0;">
                    <p class="progress">
                        Pág: <input type="number" value="${libro.pagina_actual}" min="0" max="${libro.paginas_totales}" onchange="actualizarPagina(${libro.id}, this.value, ${libro.paginas_totales})" style="width: 55px; text-align: center;"> 
                        / ${libro.paginas_totales}
                    </p>
                </div>

                <div class="actions" style="margin-top: auto; text-align: right;">
                    <button class="btn-delete" onclick="borrarLibro(${libro.id})" style="background:none; border:none; cursor:pointer; font-size:1.2rem;">❌</button>
                </div>
            </div>
        `;
        booksGrid.appendChild(card);
    });
}

// --- OPERACIÓN: MODIFICAR PAGINA / ESTADO ---
function actualizarPagina(id, nuevaPagina, total) {
    let pagina = parseInt(nuevaPagina);
    let libros = obtenerLibrosDeAlmacenamiento();
    
    libros = libros.map(libro => {
        if (libro.id === id) {
            // Validar límites
            if (pagina >= total) {
                libro.pagina_actual = total;
                libro.estado = 'Terminado';
            } else if (pagina <= 0 || isNaN(pagina)) {
                libro.pagina_actual = 0;
                libro.estado = 'Pendiente';
            } else {
                libro.pagina_actual = pagina;
                libro.estado = 'Leyendo';
            }
        }
        return libro;
    });

    guardarLibrosEnAlmacenamiento(libros);
    mostrarLibros(); // Recargar diseño visual
}

// --- OPERACIÓN: BORRAR LIBRO ---
function borrarLibro(id) {
    if (confirm('¿Seguro que querés eliminar este libro?')) {
        let libros = obtenerLibrosDeAlmacenamiento();
        libros = libros.filter(libro => libro.id !== id);
        guardarLibrosEnAlmacenamiento(libros);
        mostrarLibros();
    }
}

// --- FUNCIONES AUXILIARES DE MEMORIA ---
function obtenerLibrosDeAlmacenamiento() {
    const libros = localStorage.getItem('mis_libros');
    return libros ? JSON.parse(libros) : [];
}

function guardarLibrosEnAlmacenamiento(libros) {
    localStorage.setItem('mis_libros', JSON.stringify(libros));
}
