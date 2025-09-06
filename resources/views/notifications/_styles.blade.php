<!-- Notification System Styles -->
<style>
/* Toast Notification Styles */
.notification-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    min-width: 300px;
    max-width: 400px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border: 1px solid #e5e7eb;
    transform: translateX(100%);
    transition: transform 0.3s ease-in-out;
    overflow: hidden;
}

.notification-toast.show {
    transform: translateX(0);
}

.toast-content {
    display: flex;
    align-items: flex-start;
    padding: 16px;
}

.toast-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    flex-shrink: 0;
}

.toast-icon i {
    font-size: 16px;
    color: white;
}

.toast-message {
    flex: 1;
    min-width: 0;
}

.toast-title {
    font-weight: 600;
    font-size: 14px;
    color: #1f2937;
    margin-bottom: 4px;
    line-height: 1.4;
}

.toast-text {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.4;
}

.toast-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-left: 12px;
    flex-shrink: 0;
}

.toast-actions .btn {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    text-align: center;
    white-space: nowrap;
}

.toast-actions .btn-primary {
    background: #3b82f6;
    color: white;
}

.toast-actions .btn-primary:hover {
    background: #2563eb;
}

.toast-actions .btn-secondary {
    background: #f3f4f6;
    color: #6b7280;
}

.toast-actions .btn-secondary:hover {
    background: #e5e7eb;
    color: #374151;
}

/* Notification Dropdown Styles */
.notification-dropdown {
    position: relative;
}

.notification-count {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #dc2626; /* Higher contrast red */
    color: white;
    font-size: 10px;
    font-weight: bold;
    padding: 2px 4px;
    border-radius: 10px;
    min-width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white; /* Thicker border for better visibility */
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.notification-dropdown .dropdown-menu {
    border: 1px solid #e5e7eb;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border-radius: 8px;
    padding: 8px 0;
    min-width: 320px;
    max-width: 400px;
}

.notification-dropdown .dropdown-header {
    padding: 12px 16px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-dropdown .dropdown-header h6 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
}

.notification-dropdown .dropdown-header .btn {
    padding: 4px 8px;
    font-size: 12px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.notification-dropdown .dropdown-header .btn-sm {
    padding: 2px 6px;
    font-size: 11px;
}

.notification-dropdown .dropdown-header .text-blue-600 {
    color: #3b82f6;
}

.notification-dropdown .dropdown-header .text-blue-600:hover {
    color: #2563eb;
}

.notification-dropdown .dropdown-header .text-gray-500 {
    color: #6b7280;
}

.notification-dropdown .dropdown-header .text-gray-500:hover {
    color: #374151;
}

.notification-dropdown .notification-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f3f4f6;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.notification-dropdown .notification-item:hover {
    background-color: #f9fafb;
}

.notification-dropdown .notification-item:last-child {
    border-bottom: none;
}

.notification-dropdown .notification-item.unread {
    background-color: #eff6ff;
    border-left: 3px solid #3b82f6;
}

.notification-dropdown .notification-item.unread:hover {
    background-color: #dbeafe;
}

.notification-dropdown .notification-item-content {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.notification-dropdown .notification-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 14px;
    color: white;
}

.notification-dropdown .notification-details {
    flex: 1;
    min-width: 0;
}

.notification-dropdown .notification-title {
    font-weight: 500;
    font-size: 13px;
    color: #1f2937;
    margin-bottom: 2px;
    line-height: 1.4;
}

.notification-dropdown .notification-message {
    font-size: 12px;
    color: #6b7280;
    line-height: 1.4;
    margin-bottom: 4px;
}

.notification-dropdown .notification-time {
    font-size: 11px;
    color: #9ca3af;
}

.notification-dropdown .notification-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 6px;
    font-size: 10px;
    font-weight: 500;
    border-radius: 10px;
    background: #3b82f6;
    color: white;
    margin-top: 4px;
}

.notification-dropdown .dropdown-footer {
    padding: 12px 16px;
    border-top: 1px solid #f3f4f6;
    text-align: center;
}

.notification-dropdown .dropdown-footer a {
    color: #3b82f6;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
}

.notification-dropdown .dropdown-footer a:hover {
    color: #2563eb;
    text-decoration: underline;
}

/* Notification Bell Icon Animation */
.notification-bell {
    position: relative;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.notification-bell:hover {
    transform: scale(1.1);
}

.notification-bell.active {
    animation: bell-ring 0.5s ease-in-out;
}

@keyframes bell-ring {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(-15deg); }
    75% { transform: rotate(15deg); }
}

/* Browser Badge Styles */
@media (max-width: 768px) {
    .notification-toast {
        top: 10px;
        right: 10px;
        left: 10px;
        min-width: auto;
        max-width: none;
    }

    .notification-dropdown .dropdown-menu {
        right: 0;
        left: 0;
        min-width: auto;
        max-width: none;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .notification-toast {
        border: 2px solid #000;
    }

    .notification-dropdown .dropdown-menu {
        border: 2px solid #000;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .notification-toast {
        transition: none;
    }

    .notification-toast.show {
        transform: none;
    }

    .notification-bell {
        transition: none;
    }

    .notification-bell.active {
        animation: none;
    }
}

/* Enhanced accessibility styles */
.notification-item {
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
    background-color: #e3f2fd;
}

.notification-item:focus:not(:focus-visible) {
    outline: none;
}

.notification-item:focus-visible {
    outline: 2px solid #007bff;
    outline-offset: 2px;
    background-color: #e3f2fd;
}

/* High contrast mode improvements */
@media (prefers-contrast: high) {
    .notification-toast {
        border: 3px solid #000;
        background: #fff;
    }

    .notification-dropdown .dropdown-menu {
        border: 3px solid #000;
        background: #fff;
    }

    .notification-item {
        border: 1px solid #000;
    }

    .notification-item.unread {
        border-left: 4px solid #000;
        background-color: #e0e0e0;
    }

    .notification-title {
        color: #000;
        font-weight: bold;
    }

    .notification-message {
        color: #000;
    }

    .notification-time {
        color: #000;
        font-weight: bold;
    }

    .notification-count {
        background: #000;
        color: #fff;
        border: 2px solid #fff;
    }
}

/* Focus indicators for interactive elements */
.notification-bell:focus,
.mark-all-read-btn:focus,
.view-all-btn:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}

.notification-bell:focus:not(:focus-visible),
.mark-all-read-btn:focus:not(:focus-visible),
.view-all-btn:focus:not(:focus-visible) {
    outline: none;
}

.notification-bell:focus-visible,
.mark-all-read-btn:focus-visible,
.view-all-btn:focus-visible {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}

/* Ensure sufficient color contrast for text */
.notification-title {
    color: #1f2937; /* Dark gray for better contrast */
}

.notification-message {
    color: #4b5563; /* Medium gray for readability */
}

.notification-time {
    color: #6b7280; /* Lighter gray but still readable */
}

/* Improve contrast for notification icons */
.notification-icon {
    background: rgba(59, 130, 246, 0.15);
    border: 1px solid rgba(59, 130, 246, 0.3);
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .notification-title {
        color: #f9fafb;
    }

    .notification-message {
        color: #d1d5db;
    }

    .notification-time {
        color: #9ca3af;
    }

    .notification-item:hover {
        background-color: #374151;
    }

    .notification-item:focus {
        background-color: #1e3a8a;
    }
}
</style>
