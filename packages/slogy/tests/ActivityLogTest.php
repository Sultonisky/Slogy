<?php

namespace Sultonisky\Slogy\Tests;

use Illuminate\Database\Eloquent\Model;
use Sultonisky\Slogy\Traits\HasSlogy;
use Sultonisky\Slogy\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ActivityLogTest extends TestCase
{
    public function test_it_can_log_created_event()
    {
        $user = TestUser::create(['name' => 'Sultoni']);
        Auth::login($user);

        $product = TestProduct::create(['name' => 'Laptop']);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'created',
            'model' => TestProduct::class,
            'model_id' => $product->id,
        ]);

        $log = ActivityLog::first();
        $this->assertEquals('Laptop', $log->new_values['name']);
        $this->assertStringContainsString('Laptop', $log->description);
        $this->assertNull($log->old_values);
    }

    public function test_it_can_log_updated_event_with_diff()
    {
        $user = TestUser::create(['name' => 'Sultoni']);
        Auth::login($user);

        $product = TestProduct::create(['name' => 'Laptop']);
        
        // Update product
        $product->update(['name' => 'MacBook']);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'updated',
            'model' => TestProduct::class,
            'model_id' => $product->id,
        ]);

        $log = ActivityLog::where('action', 'updated')->first();
        $this->assertEquals('MacBook', $log->new_values['name']);
        $this->assertEquals('Laptop', $log->old_values['name']);
    }

    public function test_it_can_log_deleted_event()
    {
        $user = TestUser::create(['name' => 'Sultoni']);
        Auth::login($user);

        $product = TestProduct::create(['name' => 'Laptop']);
        $product->delete();

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'deleted',
            'model' => TestProduct::class,
            'model_id' => $product->id,
        ]);

        $log = ActivityLog::where('action', 'deleted')->first();
        $this->assertNull($log->new_values);
        $this->assertEquals('Laptop', $log->old_values['name']);
    }

    public function test_it_can_log_soft_delete_events()
    {
        $user = TestUser::create(['name' => 'Sultoni']);
        Auth::login($user);

        $product = TestProduct::create(['name' => 'Laptop']);
        
        // Soft delete
        $product->delete();
        $this->assertDatabaseHas('activity_logs', ['action' => 'deleted', 'model_id' => $product->id]);

        // Restore
        $product->restore();
        $this->assertDatabaseHas('activity_logs', ['action' => 'restored', 'model_id' => $product->id]);

        // Force delete
        $product->forceDelete();
        $this->assertDatabaseHas('activity_logs', ['action' => 'forceDeleted', 'model_id' => $product->id]);
    }

    public function test_it_can_clean_and_archive_logs()
    {
        Storage::fake('local');
        
        // Ensure retention is set to 30
        config()->set('slogy.log_retention_days', 30);

        // Create old log
        $oldLog = new ActivityLog();
        $oldLog->action = 'created';
        $oldLog->model = 'Test';
        $oldLog->model_id = 1;
        $oldLog->description = 'Old Log';
        $oldLog->created_at = Carbon::now()->subDays(40);
        $oldLog->save(['timestamps' => false]);

        // Create recent log
        $recentLog = ActivityLog::create([
            'action' => 'created',
            'model' => 'Test',
            'model_id' => 2,
            'description' => 'Recent Log',
        ]);

        $this->artisan('slogy:clean --archive')
             ->expectsOutputToContain('Successfully cleaned 1 old activity logs')
             ->assertExitCode(0);

        $this->assertDatabaseMissing('activity_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('activity_logs', ['id' => $recentLog->id]);

        // Check if archive exists in storage
        $files = Storage::disk('local')->allFiles('slogy/archives');
        $this->assertCount(1, $files);
    }

    public function test_it_uses_custom_label_in_description()
    {
        $user = TestUser::create(['name' => 'Sultoni']);
        Auth::login($user);

        $product = TestProductWithLabel::create(['name' => 'Branded Laptop']);
        
        $log = ActivityLog::first();
        $this->assertStringContainsString('Branded Laptop', $log->description);
    }

    public function test_it_respects_ignored_attributes()
    {
        config()->set('slogy.ignored_attributes', ['updated_at', 'created_at', 'name']);

        $user = TestUser::create(['name' => 'Sultoni']);
        Auth::login($user);

        $product = TestProduct::create(['name' => 'Laptop']);
        
        $log = ActivityLog::first();
        $this->assertArrayNotHasKey('name', $log->new_values);
    }
}

class TestProductWithLabel extends Model
{
    use HasSlogy;
    protected $table = 'products';
    protected $fillable = ['name'];
    public $slogyLabel = 'name';
}

class TestUser extends Model implements Authenticatable
{
    use AuthenticatableTrait;
    protected $table = 'users';
    protected $fillable = ['name'];
}

class TestProduct extends Model
{
    use HasSlogy, SoftDeletes;
    protected $table = 'products';
    protected $fillable = ['name'];
}
