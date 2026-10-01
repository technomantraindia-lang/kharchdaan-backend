<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $notifications = $user->notifications()->paginate(20);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'unread_count' => $user->unreadNotifications->count(),
                'notifications' => $notifications,
            ]);
        }

        return view('admin.notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications->count(),
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $user = auth()->user();
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read.',
            ]);
        }

        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead(Request $request)
    {
        $user = auth()->user();
        $user->unreadNotifications->markAsRead();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read.',
            ]);
        }

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }

    public function destroy(Request $request, string $id)
    {
        $user = auth()->user();
        $user->notifications()->where('id', $id)->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Notification deleted.',
            ]);
        }

        return redirect()->back()->with('success', 'Notification deleted.');
    }
}
