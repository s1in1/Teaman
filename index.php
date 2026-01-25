<?php session_start();?>
<!DOCTYPE html>
<html lang="ru" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="img/logo.svg" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&family=Roboto:wght@100..900&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <title>Тимэн</title>
  </head>
  <body>

    <!-- HEADER -->
    <?php include('header.php') ?>

      <main class="d-flex flex-nowrap" style="background: #1c1c1c;
      background: -webkit-linear-gradient(to right, #1c1c1c, #212121, #1c1c1c);
      background: linear-gradient(to right, #1c1c1c, #212121, #1c1c1c); 
      ">

      <div class="container">

        <div class="container d-flex py-5 px-3 text-center text-break text-wrap justify-content-center align-items-center mx-auto flex-column w-50">
          <img class="img-fluid mx-auto d-none d-sm-block mx-auto mb-3" src="img/logo2.svg" alt="Тимэн" width="200" height="165">
          <h1 class="mb-3 fw-semibold lh-1">Ведите дела в Тимэн.</h1>
            <p class="mb-4 fs-4 lead">Функциональный и минималистичный сайт для рабочих и личных нужд. Планируйте, отслеживайте и управляйте задачами с помощью современных инструментов.</p>
            <div class="d-flex">
              <button type="button" class="btn btn-primary btn-lg px-4 me-md-2 rounded-4 fw-medium" data-bs-toggle="modal" data-bs-target="#registerModal">Начать сейчас</button>
            </div>
        </div>

        <div class="container py-5 mb-5">
          <h2 class="display-5 fw-bold mb-4">FAQ</h2>
          
          <div class="border-bottom border-secondary mb-4">
              <h3 class="fw-semibold">Для чего это нужно?</h3>
              <p class="fs-6">Больше не нужно писать и рассылать письма, вспоминать важные задачи, отчитываться о проделанной работе - теперь все это в одном месте.</p>
          </div>
          
          <div class="border-bottom border-secondary mb-4">
              <h3 class="fw-semibold">Что мне нужно для работы?</h3>
              <p class="fs-6">Зарегистрируйтесь и войдите, создайте команду, пригласите в нее людей, создайте проект, включив вашу команду. Это все, что нужно для базовой работы.</p>
          </div>
          
          <div class="mb-4">
              <h3 class="fw-semibold">Почему мы?</h3>
              <p class="fs-6">Тимэн - это развивающееся отечественное решение с постоянной поддержкой, имеющее понятный интерфейс и богатый и легкий в освоении функционал.</p>
          </div>
        </div>

      </div>

    </main>

    <!-- FOOTER -->
    <?php include('footer.php') ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  </body>
</html>
