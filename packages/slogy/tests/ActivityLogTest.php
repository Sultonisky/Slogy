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
        $oldLog->previous_hash = null;
        $oldLog->current_hash = 'testhash';
        $oldLog->is_genesis = true;
        $oldLog->created_at = Carbon::now()->subDays(40);
        $oldLog->save(['timestamps' => false]);

        // Create recent log
        $recentLog = ActivityLog::create([
            'action' => 'created',
            'model' => 'Test',
            'model_id' => 2,
            'description' => 'Recent Log',
            'previous_hash' => 'testhash',
            'current_hash' => 'testhash2',
            'is_genesis' => false,
        ]);

        // Test Cleaning via CLI with dynamic days
        $this->artisan('slogy:clean --days=35 --archive')
             ->assertExitCode(0);

        $this->assertDatabaseMissing('activity_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('activity_logs', ['id' => $recentLog->id]);
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

    // ========== NEW TESTS FOR V2 FEATURES ========== //

    public function test_it_creates_cryptographic_chain_for_logs()
    {
        $user = TestUser::create(['name' => 'Sultoni']);
        Auth::login($user);

        // Create 3 products
        $product1 = TestProduct::create(['name' => 'Product 1']);
        $product2 = TestProduct::create(['name' => 'Product 2']);
        $product3 = TestProduct::create(['name' => 'Product 3']);

        // Get all logs in order
        $logs = ActivityLog::orderBy('id')->get();

        $this->assertCount(3, $logs);

        // Check first log is genesis
        $this->assertTrue($logs[0]->is_genesis);
        $this->assertNull($logs[0]->previous_hash);
        $this->assertNotNull($logs[0]->current_hash);

        // Check second log links to first
        $this->assertFalse($logs[1]->is_genesis);
        $this->assertEquals($logs[0]->current_hash, $logs[1]->previous_hash);
        $this->assertNotNull($logs[1]->current_hash);

        // Check third log links to second
        $this->assertFalse($logs[2]->is_genesis);
        $this->assertEquals($logs[1]->current_hash, $logs[2]->previous_hash);
        $this->assertNotNull($logs[2]->current_hash);
    }

    public function test_log_chain_verification_passes_for_valid_chain()
    {
        $user = TestUser::create(['name' => 'Sultoni']);
        Auth::login($user);

        // Create some logs
        TestProduct::create(['name' => 'Product 1']);
        TestProduct::create(['name' => 'Product 2']);

        // Verify chain
        $result = ActivityLog::verifyChain();

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['invalid_logs']);
        $this->assertEquals(2, $result['total_logs']);
    }

    public function test_log_chain_verification_fails_for_tampered_log()
    {
        $user = TestUser::create(['name' => 'Sultoni']);
        Auth::login($user);

        // Create 2 logs
        $product1 = TestProduct::create(['name' => 'Product 1']);
        $product2 = TestProduct::create(['name' => 'Product 2']);

        // Tamper with the first log
        $log1 = ActivityLog::first();
        $log1->description = 'Tampered Description';
        $log1->save();

        // Verify chain
        $result = ActivityLog::verifyChain();

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['invalid_logs']);
    }

    public function test_it_exports_to_json_with_digital_seal()
    {
        Storage::fake('local');

        $user = TestUser::create(['name' => 'Sultoni']);
        Auth::login($user);

        // Create some logs
        TestProduct::create(['name' => 'Product 1']);
        TestProduct::create(['name' => 'Product 2']);

        // Export
        $logs = ActivityLog::all();
        $filename = ActivityLog::exportToJson($logs);

        $this->assertStringContainsString('slogy-archive-', $filename);
        Storage::disk('local')->assertExists('slogy/archives/' . $filename);

        // Check file content
        $fileContent = Storage::disk('local')->get('slogy/archives/' . $filename);
        $data = json_decode($fileContent, true);

        $this->assertArrayHasKey('metadata', $data);
        $this->assertArrayHasKey('logs', $data);
        $this->assertArrayHasKey('digital_seal', $data);
        $this->assertCount(2, $data['logs']);
    }

    public function test_archive_validation_passes_for_untampered_file()
    {
        Storage::fake('local');

        $user = TestUser::create(['name' => 'Sultoni']);
        Auth::login($user);

        // Create some logs
        TestProduct::create(['name' => 'Product 1']);

        // Export
        $logs = ActivityLog::all();
        $filename = ActivityLog::exportToJson($logs);

        // Validate
        $result = ActivityLog::validateArchive('slogy/archives/' . $filename);

        $this->assertTrue($result['valid']);
        $this->assertArrayHasKey('metadata', $result);
    }

    public function test_archive_validation_fails_for_tampered_file()
    {
        Storage::fake('local');

        $user = TestUser::create(['name' => 'Sultoni']);
        Auth::login($user);

        // Create some logs
        TestProduct::create(['name' => 'Product 1']);

        // Export
        $logs = ActivityLog::all();
        $filename = ActivityLog::exportToJson($logs);

        // Tamper with the file
        $filePath = 'slogy/archives/' . $filename;
        $content = json_decode(Storage::disk('local')->get($filePath), true);
        $content['logs'][0]['description'] = 'Tampered!';
        Storage::disk('local')->put($filePath, json_encode($content));

        // Validate
        $result = ActivityLog::validateArchive($filePath);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Digital seal verification failed', $result['error']);
    }

    public function test_re_genesis_block_is_created_when_old_logs_are_cleaned()
    {
        $user = TestUser::create(['name' => 'Sultoni']);
        Auth::login($user);

        // Create 3 products to make 3 logs
        TestProduct::create(['name' => 'Product 1']);
        TestProduct::create(['name' => 'Product 2']);
        $product3 = TestProduct::create(['name' => 'Product 3']);

        // Get log IDs
        $logs = ActivityLog::orderBy('id')->get();
        $log1Id = $logs[0]->id;
        $log2Id = $logs[1]->id;
        $log3Id = $logs[2]->id;
        $log2Hash = $logs[1]->current_hash;

        // Set log 1 and 2 as old
        ActivityLog::where('id', $log1Id)->update(['created_at' => Carbon::now()->subDays(70)]);
        ActivityLog::where('id', $log2Id)->update(['created_at' => Carbon::now()->subDays(65)]);

        // Clean old logs
        ActivityLog::clean(['days' => 60]);

        // Check log 1 and 2 are deleted
        $this->assertDatabaseMissing('activity_logs', ['id' => $log1Id]);
        $this->assertDatabaseMissing('activity_logs', ['id' => $log2Id]);

        // Check log 3 is now genesis
        $remainingLog = ActivityLog::find($log3Id);
        $this->assertTrue($remainingLog->is_genesis);
        $this->assertEquals($log2Hash, $remainingLog->previous_hash);

        // Verify chain still passes
        $result = ActivityLog::verifyChain();
        $this->assertTrue($result['valid']);
    }

    public function test_verify_command_outputs_correctly_for_valid_chain()
    {
        $user = TestUser::create(['name' => 'Sultoni']);
        Auth::login($user);

        TestProduct::create(['name' => 'Product 1']);

        $this->artisan('slogy:verify')
             ->expectsOutputToContain('Total logs checked')
             ->expectsOutputToContain('Log chain is valid')
             ->assertExitCode(0);
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
