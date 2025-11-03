<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ru" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&family=Roboto:wght@100..900&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <title>teaman</title>
  </head>
  <body style="background: linear-gradient(180deg, #1c1c1c 100%, #272727ff 0%, #1c1c1c 100%);">

    <?php include('header.php') ?>

    <div class="container">

      <div class="container d-flex py-5 px-3 text-center justify-content-center align-items-center mx-auto flex-column w-50 mb-5">
        <h1 class="display-5 fw-bold mb-3">Управление проектами в Teaman.</h1>
          <p class="mb-4 fs-4">Планируйте, отслеживайте и управляйте задачами с помощью современных инструментов</p>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-mg px-4 me-md-2 rounded-4" data-bs-toggle="modal" data-bs-target="#registerModal">Начать сейчас</button>
            <button type="button" class="btn btn-outline-secondary btn-mg px-4 rounded-4" data-bs-toggle="modal" data-bs-target="#registerModal">Узнать больше</button>
          </div>
      </div>

      <div class="container py-5 mb-5">
        <h2 class="display-6 fw-bold mb-4">FAQ</h2>
        
        <div class="border-bottom border-secondary mb-4">
            <h3 class="h5 fw-bold">Для чего это нужно?</h3>
            <p class="">Больше не нужно писать и рассылать письма, вспоминать важные задачи, отчитываться о проделанной работе - теперь все это в одном месте.</p>
        </div>
        
        <div class="border-bottom border-secondary mb-4">
            <h3 class="h5 fw-bold">Что мне нужно для работы?</h3>
            <p class="">Зарегистрируйтесь и войдите, создайте проект, пригласите в команду людей и добавьте ее в проект. Это все, что нужно для базовой работы.</p>
        </div>
        
        <div class="mb-4">
            <h3 class="h5 fw-bold">Почему мы?</h3>
            <p class="">Teaman - это развивающееся отечественное решение с постоянной поддержкой, имеющее понятный интерфейс и богатый и легкий в освоении функционал.</p>
        </div>
      </div>

      <footer class="border-top py-2 mt-5">
        <div class="container">
          <div class="row align-items-center">              
            <div class="col-md-6 mb-3 mb-md-0 gap-2">
              <div class="d-flex align-items-center gap-2">
                  
                <div class="footer-icons">
                  <span><img src="/img/Logo2.svg" alt="logo"></span>
                </div>
                      
                <div class="ms-3">
                  <a href="https://t.me/Dartder" target="_blank" >
                    <i class="bi bi-telegram " style="font-size: 1.5rem; color: #86C232"></i>
                  </a>
                </div>
                      
                <div class="align-items-center justify-content-center ms-3 ">
                  <a href="mailto:mirgalimovk06@gmail.com" target="_blank" class=" text-decoration-none rounded-circle d-flex align-items-center justify-content-center" style="width: 1.5rem; height: 1.5rem; background-color: #86C232;">
                    <i class="bi bi-envelope-fill" style="font-size: 1rem; color: #1c1c1c;"></i>
                  </a>
                </div>
              </div>
            </div>

            <div class="col-md-6 text-md-end">
              <a href="#" class="text-decoration-none me-3 footer-link" style="color:#f4f4f4">Документация</a>
              <a href="#" class="text-decoration-none footer-link" style="color:#f4f4f4">Политика конфиденциальности</a>
            </div>

          </div>
        </div>
      </footer>

    </div>


 

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  </body>
</html>
