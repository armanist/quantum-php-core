<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Error Trace</title>
    </head>

    <body>

        <main>
            <h1>TRACE VIEW</h1>
            <h3><?= $severity ?> :: <?= $errorMessage ?></h3>
            <div><?= count($stackTrace) ?></div>
        </main>

    </body>
</html>
