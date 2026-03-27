<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // ============================================
    // Show unread notifications
    // ============================================

    public function index(Request $request)
    {
        return response()->json($request->user()->unreadNotifications);
    }

    // ============================================
    // Show all notifications, both read and unread
    // ============================================

    public function all(Request $request)
    {
        return response()->json($request->user()->notifications);
    }

    // ============================================
    // Mark a single notification as read
    // ============================================

    public function markAsRead(Request $request, string $id)
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

    // ============================================
    // Mark all notifications as read
    // ============================================

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read']);
    }
}
