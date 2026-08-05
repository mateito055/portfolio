<?php 
include 'conexion.php'; 

// --- OPERACIÓN: BORRAR LIBRO ---
if (isset($_GET['borrar'])) {
    $id_borrar = $_GET['borrar'];
    // Al borrar el libro, la base de datos borra su progreso automáticamente por el "ON DELETE CASCADE"
    $stmt = $pdo->prepare("DELETE FROM libros WHERE id = ?");
    $stmt->execute([$id_borrar]);
    
    header("Location: index.php");
    exit();
}

// --- OPERACIÓN: MODIFICAR PROGRESO (Actualizar Página) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'modificar') {
    $libro_id = $_POST['libro_id'];
    $nueva_pagina = $_POST['pagina_actual'];
    $paginas_totales = $_POST['paginas_totales'];
    
    // Determinar el nuevo estado automáticamente según la página actual
    $nuevo_estado = 'Leyendo';
    if ($nueva_pagina >= $paginas_totales) {
        $nueva_pagina = $paginas_totales; // Evitar que supere el máximo
        $nuevo_estado = 'Terminado';
    } elseif ($nueva_pagina <= 0) {
        $nueva_pagina = 0;
        $nuevo_estado = 'Pendiente';
    }

    $stmt = $pdo->prepare("UPDATE progreso_lectura SET pagina_actual = ?, estado = ? WHERE libro_id = ?");
    $stmt->execute([$nueva_pagina, $nuevo_estado, $libro_id]);

    header("Location: index.php");
    exit();
}

// --- OPERACIÓN: CREAR LIBRO ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['titulo'])) {
    $titulo = $_POST['titulo'];
    $autor = $_POST['autor'];
    $paginas = $_POST['paginas'];
    $estado = $_POST['estado'];

    // Buscar o insertar autor
    $stmt = $pdo->prepare("SELECT id FROM autores WHERE nombre = ?");
    $stmt->execute([$autor]);
    $autor_existente = $stmt->fetch();

    if ($autor_existente) {
        $autor_id = $autor_existente['id'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO autores (nombre) VALUES (?)");
        $stmt->execute([$autor]);
        $autor_id = $pdo->lastInsertId();
    }

    // Insertar libro
    $stmt = $pdo->prepare("INSERT INTO libros (titulo, autor_id, paginas_totales) VALUES (?, ?, ?)");
    $stmt->execute([$titulo, $autor_id, $paginas]);
    $libro_id = $pdo->lastInsertId();

    // Crear progreso (Si empieza terminado, va con el máximo de páginas)
    $pag_inicial = ($estado == 'Terminado') ? $paginas : 0;
    $stmt = $pdo->prepare("INSERT INTO progreso_lectura (libro_id, estado, pagina_actual) VALUES (?, ?, ?)");
    $stmt->execute([$libro_id, $estado, $pag_inicial]);

    header("Location: index.php");
    exit();
}

// --- OPERACIÓN: LEER (Traer todos los libros ordenados por id reciente) ---
$query = $pdo->query("
    SELECT l.id, l.titulo, a.nombre AS autor, l.paginas_totales, p.estado, p.pagina_actual
    FROM libros l
    LEFT JOIN autores a ON l.autor_id = a.id
    LEFT JOIN progreso_lectura p ON l.id = p.libro_id
    ORDER BY l.id DESC
");
$libros = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Tracker de Lectura</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>📚 Mi Biblioteca Virtual</h1>
    </header>

    <main class="container">
        <!-- Formulario de carga -->
        <section class="form-section">
            <h2>Agregar Nuevo Libro</h2>
            <form action="index.php" method="POST">
                <input type="text" name="titulo" placeholder="Título del libro" required>
                <input type="text" name="autor" placeholder="Autor" required>
                <input type="number" name="paginas" placeholder="Total de páginas" required>
                <select name="estado">
                    <option value="Pendiente">Pendiente</option>
                    <option value="Leyendo">Leyendo</option>
                    <option value="Terminado">Terminado</option>
                </select>
                <button type="submit">Guardar Libro</button>
            </form>
        </section>

        <!-- Grilla dinámica con Modificar y Borrar -->
        <section class="grid-section">
            <h2>Mis Libros</h2>
            <div class="books-grid">
                <?php foreach ($libros as $libro): ?>
                    <div class="book-card status-<?php echo strtolower($libro['estado']); ?>">
                        <img src="https://placeholder.com<?php echo urlencode($libro['titulo']); ?>" alt="Portada">
                        <div class="book-info">
                            <h3><?php echo htmlspecialchars($libro['titulo']); ?></h3>
                            <p class="author"><?php echo htmlspecialchars($libro['autor'] ?? 'Desconocido'); ?></p>
                            <span class="badge"><?php echo $libro['estado']; ?></span>
                            
                            <!-- Formulario inline para Modificar el Progreso de Páginas -->
                            <form action="index.php" method="POST" class="progress-form">
                                <input type="hidden" name="accion" value="modificar">
                                <input type="hidden" name="libro_id" value="<?php echo $libro['id']; ?>">
                                <input type="hidden" name="paginas_totales" value="<?php echo $libro['paginas_totales']; ?>">
                                
                                <p class="progress">
                                    Pág: <input type="number" name="pagina_actual" value="<?php echo $libro['pagina_actual']; ?>" min="0" max="<?php echo $libro['paginas_totales']; ?>"> 
                                    / <?php echo $libro['paginas_totales']; ?>
                                </p>
                                <button type="submit" class="btn-update" title="Guardar progreso">💾 Actualizar</button>
                            </form>

                            <div class="actions">
                                <!-- Enlace directo que pasa el ID por la URL para eliminar -->
                                <a href="index.php?borrar=<?php echo $libro['id']; ?>" class="btn-delete" onclick="return confirm('¿Seguro que querés borrar este libro?');}">❌ Eliminar</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</body>
</html>
