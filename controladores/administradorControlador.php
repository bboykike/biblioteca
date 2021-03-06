<?php
if($pretionAjax){
    require_once "../modelos/administradorModelo.php";
}else{
    require_once "./modelos/administradorModelo.php";
}

class administradorControlador extends administradorModelo{

    public function agregar_administrador_controlador(){
        
        $dni = mainModel::limpiar_cadena($_POST['dni-reg']);
        $nombre = mainModel::limpiar_cadena($_POST['nombre-reg']);
        $apellido = mainModel::limpiar_cadena($_POST['apellido-reg']);
        $telefono = mainModel::limpiar_cadena($_POST['telefono-reg']);
        $direccion = mainModel::limpiar_cadena($_POST['direccion-reg']);

        $usuario = mainModel::limpiar_cadena($_POST['usuario-reg']);
        $password1 = mainModel::limpiar_cadena($_POST['password1-reg']);
        $password2 = mainModel::limpiar_cadena($_POST['password2-reg']);
        $email = mainModel::limpiar_cadena($_POST['email-reg']);
        $genero = mainModel::limpiar_cadena($_POST['optionsGenero']);
        $privilegio = mainModel::limpiar_cadena($_POST['optionsPrivilegio']);

        if($genero == "Masculino"){
            $foto="AdminMaleAvatar.png";
        }else{
            $foto="adminmujer.png";
        }

        if($password1 != $password2){
            $alerta = [
                "Alerta"=>"simple",
                "Titulo"=>"Ocurrio un error inesperado",
                "Texto"=>"Las contraseñas ingresadas no coinciden, por favor intente nuevamente",
                "Tipo"=>"error"
            ];
        }else{

            // validar que no haya otro DNI igual en BD
            $consulta1 = mainModel::ejecutar_consulta_simple("SELECT AdminDNI FROM administrador WHERE AdminDNI='$dni'");

            if($consulta1->rowCount()>=1){
                $alerta = [
                    "Alerta"=>"simple",
                    "Titulo"=>"Ocurrio un error inesperado",
                    "Texto"=>"El DNI ingresado ya se encuentra registrado en el sistema",
                    "Tipo"=>"error"
                ];
            }else{
                if($email != ""){
                    $consulta2 = mainModel::ejecutar_consulta_simple("SELECT CuentaEmail FROM cuenta WHERE CuentaEmail='$email'");
                    $ec=$consulta2->rowCount();
                }else{
                    $ec=0;
                }

                if($ec>=1){
                    $alerta = [
                        "Alerta"=>"simple",
                        "Titulo"=>"Ocurrio un error inesperado",
                        "Texto"=>"El Email ingresado ya se encuentra registrado en el sistema",
                        "Tipo"=>"error"
                    ];
                }else{
                    $consulta3 = mainModel::ejecutar_consulta_simple("SELECT CuentaUsuario FROM cuenta WHERE CuentaUsuario='$usuario'");

                    if($consulta3->rowCount()>=1){
                        $alerta = [
                            "Alerta"=>"simple",
                            "Titulo"=>"Ocurrio un error inesperado",
                            "Texto"=>"El Usuario ingresado ya se encuentra registrado en el sistema",
                            "Tipo"=>"error"
                        ];                        
                    }else{
                        $consulta4 = mainModel::ejecutar_consulta_simple("SELECT id FROM cuenta");
                        $numero=($consulta4->rowCount())+1;

                        $codigo=mainModel::generar_codigo_aleatorio("AC",7,$numero);
                        $clave=mainModel::encryption($password2);
                        $dataAC = [
                            "Codigo"=>$codigo,
                            "Privilegio"=>$privilegio,
                            "Usuario"=>$usuario,
                            "Clave"=>$clave,
                            "Email"=>$email,
                            "Estado"=>"Activo",
                            "Tipo"=>"Administrador",
                            "Genero"=>$genero,
                            "Foto"=>$foto
                        ];

                        $guardarCuenta=mainModel::agregar_cuenta($dataAC);

                        // comprobar si la cuenta se registro
                        if($guardarCuenta->rowCount()>=1){
                            $dataAD = [
                                "DNI"=>$dni,
                                "Nombre"=>$nombre,
                                "Apellido"=>$apellido,
                                "Telefono"=>$telefono,
                                "Direccion"=>$direccion,
                                "Codigo"=>$codigo
                            ];                   
                            
                            $guardarAdmin = administradorModelo::agregar_administrador_modelo($dataAD);

                            if($guardarAdmin->rowCount()>=1){
                                $alerta = [
                                    "Alerta"=>"limpiar",
                                    "Titulo"=>"Administrador registrado",
                                    "Texto"=>"El administrador se registro con éxito en el sistema",
                                    "Tipo"=>"success"
                                ]; 
                            }else{

                                mainModel::eliminar_cuenta($codigo);
                                $alerta = [
                                    "Alerta"=>"simple",
                                    "Titulo"=>"Ocurrio un error inesperado",
                                    "Texto"=>"No se ha podido registrar el administrador",
                                    "Tipo"=>"error"
                                ]; 
                            }

                        }else{
                            $alerta = [
                                "Alerta"=>"simple",
                                "Titulo"=>"Ocurrio un error inesperado",
                                "Texto"=>"No se ha podido registrar el administrador",
                                "Tipo"=>"error"
                            ]; 
                        }

                    }
                }
            }
        }

        return mainModel::sweet_alert($alerta);

    }

    //Controlador para Paginador administrador
    public function paginador_administrador_controlador($pagina, $registros, $privilegio, $codigo){

        //Se iimpia para evitar inyecciones sql.
        $pagina=mainModel::limpiar_cadena($pagina);
        $registros=mainModel::limpiar_cadena($registros);
        $privilegio=mainModel::limpiar_cadena($privilegio);
        $codigo=mainModel::limpiar_cadena($codigo);
        $tabla="";

        //Para tomar solo el primer valor que viene por la URL del paginador.
        $pagina = (isset($pagina) && $pagina > 0) ? (int) $pagina :1;
        $inicio = ($pagina>0) ? (($pagina*$registros)-$registros) : 0;

        $conexion = mainModel::conectar();

        $datos = $conexion->query("
            SELECT SQL_CALC_FOUND_ROWS * FROM administrador WHERE CuentaCodigo!='$codigo' AND id!='1' ORDER BY AdminNombre ASC LIMIT $inicio, $registros
        ");

        $datos = $datos->fetchAll();

        $total = $conexion->query("SELECT FOUND_ROWS()");
        $total =(int) $total->fetchColumn();

        //Mostrar dinamicamente el número de contador de páginas.
        $Npaginas = ceil($total/$registros);
        $tabla.='
        <div class="table-responsive">
                <table class="table table-hover text-center">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th class="text-center">DNI</th>
                            <th class="text-center">NOMBRES</th>
                            <th class="text-center">APELLIDOS</th>
                            <th class="text-center">TELÉFONO</th>
                            <th class="text-center">A. CUENTA</th>
                            <th class="text-center">A. DATOS</th>
                            <th class="text-center">ELIMINAR</th>
                        </tr>
                    </thead>
                    <tbody>
        ';

        if($total>=1 && $pagina<=$Npaginas){
            $contador = $inicio+1;
            foreach($datos as $rows){
                $tabla.='
                <tr>
                <td>'.$contador.'</td>
                <td>'.$rows['AdminDNI'].'</td>
                <td>'.$rows['AdminNombre'].'</td>
                <td>'.$rows['AdminApellido'].'</td>
                <td>'.$rows['AdminTelefono'].'</td>
                <td>
                    <a href="#!" class="btn btn-success btn-raised btn-xs">
                        <i class="zmdi zmdi-refresh"></i>
                    </a>
                </td>
                <td>
                    <a href="#!" class="btn btn-success btn-raised btn-xs">
                        <i class="zmdi zmdi-refresh"></i>
                    </a>
                </td>
                <td>
                    <form>
                        <button type="submit" class="btn btn-danger btn-raised btn-xs">
                            <i class="zmdi zmdi-delete"></i>
                        </button>
                    </form>
                </td>
            </tr>                
                ';
                $contador++;
            }
        }else{
            if($total>=1){
                $tabla.='
                <tr>
                    <a href="'.SERVERURL.'adminlist/" class="btn btn-sm btn-info btn-raised">
                        Haga click aquí para recargar el listado
                    </a>
                </tr>            
                ';
            }else{
                $tabla.='
                <tr>
                    <td colspan="6">No hay registros en el sistema</td>
                </tr>            
                ';
            }
           
        }
        
        $tabla.='</tbody></table></div>';
        return $tabla;

    }
}