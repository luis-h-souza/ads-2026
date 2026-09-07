<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $viewPath, array $data = [], ?string $layout = 'layouts/header'): void
    {
        // Extrai variáveis para o escopo da view
        extract($data, EXTR_SKIP);

        $viewsBaseDir = dirname(__DIR__) . '/Views/';
        $targetFile = $viewsBaseDir . trim($viewPath, '/') . '.php';

        if (!is_file($targetFile)) {
            throw new RuntimeException("View não encontrada: {$targetFile}");
        }

        // Variáveis globais úteis na view
        $authUser = Auth::user();
        $isLoggedIn = Auth::check();

        // Inicia captura do buffer
        ob_start();
        require $targetFile;
        $content = ob_get_clean();

        // Se tiver layout (por exemplo um template com header e footer conjuntos ou invocado diretamente)
        // Se a view já incluir seu próprio layout completo, ela pode passar $layout = null
        if ($layout !== null && is_file($viewsBaseDir . trim($layout, '/') . '.php')) {
            require_once $viewsBaseDir . trim($layout, '/') . '.php';
            echo $content;
            $footerFile = $viewsBaseDir . 'layouts/footer.php';
            if (is_file($footerFile)) {
                require_once $footerFile;
            }
        } else {
            echo $content;
        }
    }
}
