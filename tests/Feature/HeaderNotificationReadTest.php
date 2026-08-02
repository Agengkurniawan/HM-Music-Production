<?php

namespace Tests\Feature;

use App\Models\StyleSampling;
use App\Models\User;
use App\Support\HeaderNotificationFactory;
use App\Support\HeaderNotificationReadState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderNotificationReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_notification_is_removed_from_unread_after_marking_read(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'status' => 'Active',
        ]);

        StyleSampling::create([
            'name' => 'Fresh Dangdut',
            'category' => 'Dangdut',
            'pack' => 'HM Dangdut Expansion Packs',
            'access' => 'Premium',
            'status' => 'Published',
            'style_file_path' => 'styles/fresh-dangdut.sty',
        ]);

        $factory = app(HeaderNotificationFactory::class);
        $notifications = $factory->forCustomer($user);
        $notification = $notifications->firstWhere('title', 'New style update');

        $this->assertNotNull($notification);

        $this->actingAs($user)
            ->postJson(route('notifications.read'), ['key' => $notification['key']])
            ->assertOk()
            ->assertJson(['read' => true]);

        $unread = app(HeaderNotificationReadState::class)
            ->unread($factory->forCustomer($user), $user);

        $this->assertSame($notifications->count() - 1, $unread->count());
        $this->assertFalse($unread->contains(fn (array $item): bool => $item['key'] === $notification['key']));
        $this->assertDatabaseHas('header_notification_reads', [
            'user_id' => $user->id,
            'notification_key' => $notification['key'],
        ]);
    }

    public function test_admin_notification_is_removed_from_unread_after_marking_read(): void
    {
        $admin = User::factory()->create([
            'email' => config('hm.admin_email'),
            'email_verified_at' => now(),
            'role' => 'admin',
            'status' => 'Active',
        ]);

        User::factory()->create([
            'role' => 'customer',
            'status' => 'Pending',
        ]);

        $factory = app(HeaderNotificationFactory::class);
        $notifications = $factory->forAdmin();
        $notification = $notifications->firstWhere('title', 'Customer account pending');

        $this->assertNotNull($notification);

        $this->actingAs($admin)
            ->postJson(route('notifications.read'), ['key' => $notification['key']])
            ->assertOk()
            ->assertJson(['read' => true]);

        $unread = app(HeaderNotificationReadState::class)
            ->unread($factory->forAdmin(), $admin);

        $this->assertSame($notifications->count() - 1, $unread->count());
        $this->assertFalse($unread->contains(fn (array $item): bool => $item['key'] === $notification['key']));
        $this->assertDatabaseHas('header_notification_reads', [
            'user_id' => $admin->id,
            'notification_key' => $notification['key'],
        ]);
    }
}
