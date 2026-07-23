<?php
//Esto siempre va arriba
declare(strict_types=1);

//Activar el reporte de errores
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/includes/session_init.php';

//Incluimos la conexión de la base de datos
require_once 'conexion.php';

//validamos que el usuario y la contrasñea hayan sido enviados por el formulario

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioInput = $_POST['usuario'] ?? '';
    $passwordInput = $_POST['password'] ?? '';

    try{
        //Consultamos el usuario de forma segura a la base de datos utilizando consultas de tipo statement
        $stmt = $pdo_posventa->prepare("SELECT id, usuario, password_hash, rol FROM usuarios WHERE usuario = ? AND estado=1");
        // Ejecutar la consulta pasando el parámetro del usuario
        $stmt->execute([$usuarioInput]);
        
        $usuarioDB = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $loginValido = false;
        if ($usuarioDB) {
            $hashAlmacenado = (string) ($usuarioDB['password_hash'] ?? '');
            $loginValido = password_verify($passwordInput, $hashAlmacenado);

            if (!$loginValido && hash_equals($hashAlmacenado, $passwordInput)) {
                $nuevoHash = password_hash($passwordInput, PASSWORD_DEFAULT);
                $actualizar = $pdo->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
                $actualizar->execute([$nuevoHash, $usuarioDB['id']]);
                $loginValido = true;
            }
        }

        if($usuarioDB && $loginValido){
            //Si son correctas, creamos la sesión del usuario
            $_SESSION['usuario_activo'] = [
                'id' => $usuarioDB['id'],
                'usuario' => $usuarioDB['usuario'],
                'rol' => $usuarioDB['rol']
            ];
            header('Location: ../dashboard.php');
            exit();
        }else{
         //Login fallido
            header('Location: ../index.php?error=1');
            exit();   
        }

    }catch(PDOException $e){

        die("Error en la base de datos: ". $e->getMessage());
        exit();
    }
}else{
    //Si no se envió el formulario, redirigimos al index
    header('Location: ../index.php');
    exit();
}
