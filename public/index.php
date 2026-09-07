<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Config/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\SemesterController;
use App\Controllers\StudyController;
use App\Core\App;
use App\Core\Auth;
use App\Core\Response;

$app = new App();
$router = $app->getRouter();

// Controllers
$auth = new AuthController();
$dashboard = new DashboardController();
$semesters = new SemesterController();
$study = new StudyController();

// ─── ROTAS DE PÁGINAS (VIEWS) ────────────────────────────────────────────────
$router->get('/', function () {
    Response::redirect(base_url('/dashboard'));
});
$router->get('/login', [$auth, 'showLogin']);
$router->get('/dashboard', [$dashboard, 'index']);
$router->get('/logout', [$auth, 'logout']);

// ─── ROTAS DE API & ENDPOINTS ────────────────────────────────────────────────
$router->get('/health', fn() => Response::json(['status' => 'ok', 'app' => 'ads-2026']));
$router->get('/api/health', fn() => Response::json(['status' => 'ok', 'app' => 'ads-2026']));
$router->get('/api/me', [$auth, 'me']);

// Autenticação
$router->post('/auth/register', [$auth, 'register']);
$router->post('/api/auth/register', [$auth, 'register']);
$router->post('/auth/login', [$auth, 'login']);
$router->post('/api/auth/login', [$auth, 'login']);
$router->post('/auth/logout', [$auth, 'logout']);
$router->post('/api/auth/logout', [$auth, 'logout']);

// Semestres
$router->get('/semestres', [$semesters, 'index']);
$router->get('/api/semestres', [$semesters, 'index']);
$router->post('/semestres', [$semesters, 'store']);
$router->post('/api/semestres', [$semesters, 'store']);
$router->get('/semestres/{id}', fn(int $id) => $semesters->show($id));
$router->get('/api/semestres/{id}', fn(int $id) => $semesters->show($id));
$router->patch('/semestres/{id}', fn(int $id) => $semesters->update($id));
$router->patch('/api/semestres/{id}', fn(int $id) => $semesters->update($id));
$router->delete('/semestres/{id}', fn(int $id) => $semesters->delete($id));
$router->delete('/api/semestres/{id}', fn(int $id) => $semesters->delete($id));

// Disciplinas
$router->post('/disciplinas', [$study, 'storeDiscipline']);
$router->post('/api/disciplinas', [$study, 'storeDiscipline']);
$router->patch('/disciplinas/{id}', fn(int $id) => $study->updateDiscipline($id));
$router->patch('/api/disciplinas/{id}', fn(int $id) => $study->updateDiscipline($id));
$router->delete('/disciplinas/{id}', fn(int $id) => $study->deleteDiscipline($id));
$router->delete('/api/disciplinas/{id}', fn(int $id) => $study->deleteDiscipline($id));

// Unidades
$router->post('/unidades', [$study, 'storeUnit']);
$router->post('/api/unidades', [$study, 'storeUnit']);
$router->patch('/unidades/{id}', fn(int $id) => $study->updateUnit($id));
$router->patch('/api/unidades/{id}', fn(int $id) => $study->updateUnit($id));
$router->delete('/unidades/{id}', fn(int $id) => $study->deleteUnit($id));
$router->delete('/api/unidades/{id}', fn(int $id) => $study->deleteUnit($id));

// Itens de Estudo
$router->post('/itens-estudo', [$study, 'storeItem']);
$router->post('/api/itens-estudo', [$study, 'storeItem']);
$router->patch('/itens-estudo/{id}', fn(int $id) => $study->updateItem($id));
$router->patch('/api/itens-estudo/{id}', fn(int $id) => $study->updateItem($id));
$router->delete('/itens-estudo/{id}', fn(int $id) => $study->deleteItem($id));
$router->delete('/api/itens-estudo/{id}', fn(int $id) => $study->deleteItem($id));
$router->patch('/itens-estudo/{id}/progresso', fn(int $id) => $study->updateProgress($id));
$router->patch('/api/itens-estudo/{id}/progresso', fn(int $id) => $study->updateProgress($id));

// Executa a aplicação
$app->run();
