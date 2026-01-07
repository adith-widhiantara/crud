<?php

namespace Adithwidhiantara\Crud\Tests\Unit;

use Adithwidhiantara\Crud\CrudServiceProvider;
use Adithwidhiantara\Crud\Tests\Fixtures\Controllers\ChildController;
use Adithwidhiantara\Crud\Tests\Fixtures\Controllers\DummyController;
use Adithwidhiantara\Crud\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Mockery;
use ReflectionMethod;

class CrudServiceProviderTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // Point to our fixtures
        $app['config']->set('crud.controllers_path', __DIR__.'/../Fixtures/Controllers');
        $app['config']->set('crud.prefix', 'api/v1');
    }

    public function test_it_merges_config()
    {
        // 'crud' config key should be present
        $this->assertNotNull(config('crud'));
        // Verify prefix from env setup is respected
        $this->assertEquals('api/v1', config('crud.prefix'));
    }

    public function test_it_publishes_config()
    {
        $this->artisan('vendor:publish', [
            '--provider' => CrudServiceProvider::class,
            '--tag' => 'crud-config',
        ])->assertExitCode(0);

        $this->assertFileExists(config_path('crud.php'));

        // Cleanup
        @unlink(config_path('crud.php'));
    }

    public function test_it_registers_commands()
    {
        $this->assertArrayHasKey('make:crud', Artisan::all());
    }

    public function test_it_skips_route_registration_if_routes_are_cached()
    {
        // Mock the application to return true for routesAreCached
        $appMock = Mockery::mock($this->app)->makePartial();
        $appMock->shouldReceive('routesAreCached')->andReturn(true);

        $provider = new CrudServiceProvider($appMock);

        // Use reflection to call registerDynamicRoutes
        $reflection = new ReflectionMethod($provider, 'registerDynamicRoutes');
        // $reflection->setAccessible(true); // Public by default in recent PHP if protected, but safe to call.

        // Execute
        $reflection->invoke($provider);

        $this->assertTrue(true, 'Should return early without error');
    }

    public function test_it_aborts_if_controller_path_does_not_exist()
    {
        // Change config to invalid path
        config()->set('crud.controllers_path', '/path/to/nowhere');

        // Re-run registration
        $provider = new CrudServiceProvider($this->app);
        $reflection = new ReflectionMethod($provider, 'registerDynamicRoutes');
        $reflection->invoke($provider);

        // Should return early, no exception
        $this->assertTrue(true);
    }

    public function test_it_ignores_invalid_controllers()
    {
        // Only Dummy and Child should be registered
        // Check for 'plain-controllers' (PlainController)
        $this->assertNull($this->findRoute('GET', 'api/v1/plain-controllers'));

        // Check for 'abstract-fixture-controllers' (AbstractFixtureController)
        $this->assertNull($this->findRoute('GET', 'api/v1/abstract-fixture-controllers'));

        // BadController matches nothing
    }

    public function test_it_registers_routes_for_dummy_controller()
    {
        // 1. Standard Resource Route "index"
        $this->assertTrue(Route::has('dummy-models.index'));
        $route = Route::getRoutes()->getByName('dummy-models.index');
        $this->assertEquals('api/v1/dummy-models', $route->uri);
        $this->assertEquals(DummyController::class.'@index', $route->action['controller']);

        // 2. Custom Method with Attribute (custom-uri)
        $customRoute = $this->findRoute('GET', 'api/v1/dummy-models/custom-uri');
        $this->assertNotNull($customRoute);
        $this->assertEquals(DummyController::class.'@customMethod', $customRoute->action['controller']);

        // 3. Public Method without Attribute (public-method/{id})
        $publicRoute = $this->findRoute('GET', 'api/v1/dummy-models/public-method/{id}');
        $this->assertNotNull($publicRoute, 'Public method routes should be registered automatically');
        $this->assertEquals(DummyController::class.'@publicMethod', $publicRoute->action['controller']);

        // 4. Public Method without Attribute with class as a parameters (public-method/{id})
        $publicRoute = $this->findRoute('GET', 'api/v1/dummy-models/public-method-with-request/{id}');
        $this->assertNotNull($publicRoute, 'Public method routes should be registered automatically');
        $this->assertEquals(DummyController::class.'@publicMethodWithRequest', $publicRoute->action['controller']);
    }

    public function test_it_registers_inherited_routes_logic()
    {
        // ChildController extends DummyController defaults to "child-models" slug.

        // 1. Inherited Custom Endpoint (customMethod) -> Should be present
        $inheritedCustom = $this->findRoute('GET', 'api/v1/child-models/custom-uri');
        $this->assertNotNull($inheritedCustom, 'Inherited methods with #[Endpoint] should be registered');
        $this->assertEquals(ChildController::class.'@customMethod', $inheritedCustom->action['controller']);

        // 2. Inherited Public Method (publicMethod) -> Should NOT be present
        $inheritedPublic = $this->findRoute('GET', 'api/v1/child-models/public-method/{id}');
        $this->assertNull($inheritedPublic, 'Inherited public methods WITHOUT #[Endpoint] should NOT be registered');

        // 3. Inherited Bulk (from BaseCrudController) -> Has Endpoint -> Should be present
        $inheritedBulk = $this->findRoute('POST', 'api/v1/child-models/bulk');
        $this->assertNotNull($inheritedBulk, 'Inherited methods from Base with #[Endpoint] should be registered');
        $this->assertEquals(ChildController::class.'@bulk', $inheritedBulk->action['controller']);
    }

    protected function findRoute($method, $uri)
    {
        $routes = Route::getRoutes()->getRoutes();
        foreach ($routes as $route) {
            if (in_array($method, $route->methods) && $route->uri === $uri) {
                return $route;
            }
        }

        return null;
    }
}
