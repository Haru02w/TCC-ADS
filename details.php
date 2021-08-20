<?php
session_name("HATIDS");
session_start();
date_default_timezone_set('America/Sao_Paulo');
require('connection.php');
require('functions.php');

if (isset($_COOKIE['EMAIL']) && isset($_COOKIE['TYPE'])) {
    $email = $_COOKIE['EMAIL'];
    $type = $_COOKIE['TYPE'];
} else if (isset($_SESSION['EMAIL']) && isset($_SESSION['TYPE'])) {
    if (isset($_SESSION['LAST_ACTIVITY']) && time() - $_SESSION['LAST_ACTIVITY'] > 60 * 30) {
        expiredReturn();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
    $email = $_SESSION['EMAIL'];
    $type = $_SESSION['TYPE'];
} else {
    expiredReturn();
}

if (!isset($_GET['ids'])) {
    header("Location: /" . strtolower($type) . "menu.php");
    exit();
}
$ids = $_GET['ids'];

$resultserv = searchServices($ids, $conn);
if ($resultserv->num_rows <= 0) {
    header("Location: /" . strtolower($type) . "menu.php");
    exit();
}

$rowuser = mysqli_fetch_assoc(searchEmailType($email, $type, $conn));
$rowser = mysqli_fetch_assoc($resultserv);
if (is_null($rowuser)) {
    expiredReturn();
}



$id = $rowuser["ID_$type"];

$iddev = $rowser['COD_DEVELOPER'];
$idcus = $rowser['COD_CUSTOMER'];



$infodev = searchInfoDev($iddev, $conn);
$infocus = searchInfoCus($idcus, $conn);

if ($rowser['STATUS'] >= 1) {
    $birthdev = explode("-", $infodev['BIRTH_DATE']);
    $infodev['BIRTH_DATE'] = $birthdev[2] . "/" . $birthdev[1] . "/" . $birthdev[0];
}

$birthcus = explode("-", $infocus['BIRTH_DATE']);
$infocus['BIRTH_DATE'] = $birthcus[2] . "/" . $birthcus[1] . "/" . $birthcus[0];


$stmt5 = mysqli_prepare($conn, "SELECT COD_SERVICE FROM TB_RATING");
$cod_service  = mysqli_stmt_execute($stmt5);


if (isset($_POST['REQUEST'])) {
    $status = searchServices($ids, $conn);
    $status = $status['STATUS'];

    if ($status >= 1) {
        $_SESSION['detail'] = "takend";
        header("Location: /pendingservices/");
        exit();
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE TB_SERVICES SET COD_DEVELOPER = ?, STATUS = 1 WHERE ID_SERVICE = ?");
        mysqli_stmt_bind_param($stmt, "ss", $id, $ids);
        $bool = mysqli_stmt_execute($stmt);

        if ($bool) {
            $_SESSION['detail'] = "successd";
            header("Location: /pendingservices/");
            exit();
        } else {
            $_SESSION['detail'] = "failured";
            header("Location: /pendingservices/");
            exit();
        }
    }
} elseif (isset($_POST['SEND'])) {
    $stmt2 = mysqli_prepare($conn, "UPDATE TB_SERVICES SET STATUS = 2 WHERE ID_SERVICE = ?");
    mysqli_stmt_bind_param($stmt2, "s", $ids);
    $bool = mysqli_stmt_execute($stmt2);

    if ($bool) {
        $_SESSION['send'] = "successs";
        header("Location: /developmentservices/");
        exit();
    } else {
        $_SESSION['send'] = "failures";
        header("Location: /developmentservices/");
        exit();
    }
} elseif (isset($_POST['SENDRECUSE'])) {
    $stmt3 = mysqli_prepare($conn, "UPDATE TB_SERVICES SET COD_DEVELOPER = NULL, STATUS = 0 WHERE ID_SERVICE = ?");
    mysqli_stmt_bind_param($stmt3, "s", $ids);
    $bool = mysqli_stmt_execute($stmt3);

    if ($bool) {
        $_SESSION['recuse'] = "successre";
        header("Location: /pendingservices/");
        exit();
    } else {
        $_SESSION['recuse'] = "failurere";
        header("Location: /pendingservices/");
        exit();
    }
} elseif (isset($_POST['REPORT'])) {
    $id_service = $rowser['ID_SERVICE'];
    $type_report = $_POST['cont'];
    $stmt3 = mysqli_prepare($conn, "INSERT INTO TB_REPORT(COD_SERVICE, TYPE_REPORT) VALUES (?,?)");
    mysqli_stmt_bind_param($stmt3, "ss", $id_service, $type_report);
    $bool = mysqli_stmt_execute($stmt3);
}



if ($type == "CUSTOMER") {
    if ($idcus !== $id) {
        header("Location: /customermenu/");
        exit();
    }
} elseif ($type == "DEVELOPER" and $rowser['STATUS'] >= 1) {
    if ($iddev !== $id) {
        header("Location: /developermenu/");
        exit();
    }
}



mysqli_close($conn);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hatchfy</title>
    <link rel="stylesheet" href="https://hatchfy.philadelpho.tk/css/style.css">
    <script src="https://hatchfy.philadelpho.tk/js/vue.js"></script>
    <script src="https://hatchfy.philadelpho.tk/js/jscript.js"></script>
    <script src="https://hatchfy.philadelpho.tk/js/v-mask.min.js"></script>
    <script src="https://hatchfy.philadelpho.tk/js/moment.js"></script>
</head>

<body class="background">
    <div id="app" class="script">
        <?php if ($type == "CUSTOMER") {
            require("headercustomer.php");
        } else {
            require("headerdeveloper.php");
        } ?>
        <br>
        <section class="hero is-fullheight">
            <div class="hero-body">
                <div class="container">
                    <section class="hero is-dark">
                        <div class="hero-body is-dark">
                            <p class="title">
                                Detalhes do serviço
                            </p>
                        </div>
                    </section>
                    <form action="" method="POST">
                        <div class="section">
                            <div class="columns">
                                <div class="column is-5">
                                    <div class="field">
                                        <label class="label">Título do serviço</label>
                                        <div class="box">
                                            <p class="subtitle is-5"><?php echo $rowser['TITLE']; ?></p>
                                        </div>
                                    </div>
                                    <div class="control">
                                        <label class="label" for="description">Descrição do serviço</label>
                                        <div class="box">
                                            <p class="subtitle is-5"><?php echo $rowser['DESCRIPTION']; ?></p>
                                        </div>
                                    </div>
                                    <br>
                                    <?php if ($rowser['STATUS'] == 0) { ?>
                                        <a class="button is-danger is-medium" @click="onClickButtonModal">Reportar</a>
                                        <div class="modal" :class="{'is-active': isActiveModal}">
                                            <div class="modal-background"></div>
                                            <div class="modal-card">
                                                <header class="modal-card-head">
                                                    <p class="modal-card-title">Reportar o Serviço</p>
                                                    <button class="delete" aria-label="close" @click="onClickButtonModal"></button>
                                                </header>
                                                <section class="modal-card-body">
                                                    <div class="control">
                                                        <label class="radio is-large">
                                                            <input type="radio" name="cont" value="cont-sexual">
                                                            Conteúdo sexual
                                                        </label>
                                                        <br>
                                                        <label class="radio">
                                                            <input type="radio" name="cont" value="cont-violence">
                                                            Conteúdo violento ou repulsivo
                                                        </label>
                                                        <br>
                                                        <label class="radio">
                                                            <input type="radio" name="cont" value="cont-rate-abuse">
                                                            Conteúdo de incitação ao ódio ou abusivo
                                                        </label>
                                                        <br>
                                                        <label class="radio">
                                                            <input type="radio" name="cont" value="cont-spam">
                                                            Spam ou enganoso
                                                        </label>
                                                        <br>
                                                        <label class="radio">
                                                            <input type="radio" name="cont" value="cont-other">
                                                            Outro
                                                        </label>
                                                        <br>
                                                        <button type="submit" class="button" name="REPORT">Enviar Denúncia</button>

                                                    </div>
                                                </section>
                                            </div>
                                        </div>

                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <?php if ($type == "CUSTOMER") { ?>
                            <section class="hero is-dark">
                                <div class="hero-body is-dark">
                                    <p class="title">
                                        Informações do desenvolvedor
                                    </p>
                                </div>
                            </section>
                            <div class="section">
                                <?php if ($rowser['STATUS'] >= 1) { ?>
                                    <div class="columns">
                                        <div class="column is-3">
                                            <div class="field">
                                                <label class="label has-text-centered"> Foto do desenvolvedor </label>
                                                <figure class="image is-square">
                                                    <img class="is-rounded" src="<?php echo $infodev['IMAGE'] ?>">
                                                </figure>
                                                <br>
                                                <?php if ($rowser['STATUS'] == 3 and $rowser['ID_SERVICE'] == $cod_service) { ?>
                                                    <div class="buttons is-centered"><button type="button" class="button is-success is-medium" @click="onClickButtonModal">Avaliar</button></div>
                                                    <div class="modal" :class="{'is-active': isActiveModal}">
                                                        <div class="modal-background"></div>
                                                        <div class="modal-card">
                                                            <header class="modal-card-head">
                                                                <p class="modal-card-title">Avaliar Desenvolvedor</p>
                                                                <button class="delete" aria-label="close" @click="onClickButtonModal"></button>
                                                            </header>
                                                            <section class="modal-card-body">
                                                                <form method="POST" action="" enctype="multipart/form-data">
                                                                    <div class="estrelas">
                                                                        <input type="radio" id="vazio" name="estrela" value="" checked>
                                                                        <label for="estrela_1"><i class="fa"></i></label>
                                                                        <input type="radio" id="estrela_1" name="estrela" value="1">

                                                                        <label for="estrela_2"><i class="fa"></i></label>
                                                                        <input type="radio" id="estrela_2" name="estrela" value="2">

                                                                        <label for="estrela_3><i class=" fa"></i></label>
                                                                        <input type="radio" id="estrela_3" name="estrela" value="3">

                                                                        <label for="estrela_4"><i class="fa"></i></label>
                                                                        <input type="radio" id="estrela_4" name="estrela" value="4">

                                                                        <label for="estrela_5"><i class="fa"></i></label>
                                                                        <input type="radio" id="estrela_5" name="estrela" value="5">

                                                                        <label for="estrela_6"><i class="fa"></i></label>
                                                                        <input type="radio" id="estrela_6" name="estrela" value="6">
                                                                    </div>
                                                                </form>
                                                            </section>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div>

                                        </div>
                                        <div class="column">
                                            <div class="field">
                                                <label class="label">Nome</label>
                                                <div class="box">
                                                    <p class="subtitle is-5"><?php echo $infodev['NAME']; ?></p>
                                                </div>
                                            </div>
                                            <div class="field">
                                                <label class="label" for="description">Email</label>
                                                <div class="box">
                                                    <p class="subtitle is-5"><?php echo $infodev['EMAIL']; ?></p>
                                                </div>
                                            </div>
                                            <div class="field">
                                                <label class="label" for="contact">Data de nascimento</label>
                                                <div class="box">
                                                    <p class="subtitle is-5"><?php echo $infodev['BIRTH_DATE']; ?></p>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                <?php } ?>
                                <?php if ($rowser['STATUS'] <= 0) { ?>
                                    <div class="box">
                                        <p class="title is-5"> Ainda não há um desenvolvedor! </p>
                                    </div>
                                <?php } ?>
                                <?php if ($rowser['STATUS'] == 1) { ?>
                                    <div class="section has-text-centered">
                                        <div class="field">
                                            <button class="button is-medium is-primary" name="SEND" type="submit">Aceitar pedido</button>
                                            <button class="button is-medium is-danger" name="SENDRECUSE" type="submit">Recusar pedido</button>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                        <?php if ($type == "DEVELOPER") { ?>
                            <section class="hero is-dark">
                                <div class="hero-body is-dark">
                                    <p class="title">
                                        Informações do cliente
                                    </p>
                                </div>
                            </section>
                            <div class="section">
                                <div class="columns">
                                    <div class="column is-3">
                                        <div class="field">
                                            <label class="label has-text-centered">Foto do cliente</label>
                                            <figure class="image is-square">
                                                <img class="is-rounded" src='<?php echo $infocus['IMAGE']; ?>'>
                                            </figure>
                                        </div>
                                    </div>
                                    <div class="column">
                                        <div class="field">
                                            <label class="label">Nome</label>
                                            <div class="box">
                                                <p class="subtitle is-5"><?php echo $infocus['NAME']; ?></p>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <label class="label">Email</label>
                                            <div class="box">
                                                <p class="subtitle is-5"><?php echo $infocus['EMAIL']; ?></p>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <label class="label">Data de nascimento</label>
                                            <div class="box">
                                                <p class="subtitle is-5"><?php echo $infocus['BIRTH_DATE']; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($rowser['STATUS'] == 0) { ?>
                                    <div class="section has-text-centered">
                                        <div class="field">
                                            <button class="button is-medium is-primary" name="REQUEST" type="submit"> Enviar solicitação </button>
                                        </div>
                                    </div>
                                <?php } ?>
                                <?php if ($rowser['STATUS'] == 1) { ?>
                                    <div class="section has-text-centered">
                                        <div class="notification is-primary">
                                            <p class="title is-5"> Aguardando a confirmação pelo cliente...</p>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </form>
                </div>
            </div>
        </section>


    </div>
    <noscript>
        <style>
            .script {
                display: none;
            }
        </style>
        <section class="hero is-fullheight">
            <div class="hero-body">
                <div class="container has-text-centered">
                    <div class="box has-text-centered">
                        <p class="title font-face"> JavaScript não habilitado! </p> <br>
                        <p class="title is-5"> Por favor, habilite o JavaScript para a página funcionar! </p>
                    </div>
                </div>
            </div>
        </section>
    </noscript>
    <script>
        new Vue({
            el: '#app',
            data: {
                isActiveBurger: false,
                isActiveModal: false,
            },
            methods: {
                onClickBurger() {
                    this.isActiveBurger = !this.isActiveBurger
                },
                onClickLogout() {
                    window.location.replace("/logout/")
                },
                onClickButtonModal() {
                    this.isActiveModal = !this.isActiveModal
                }
            }
        })
    </script>
</body>

</html>