{{-- Notification System Styles --}}
<style>
/* Notification Bell */
.notification-bell {
    position: relative;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: all 0.3s ease;
    color: #6c757d;
}

.notification-bell:hover {
    background-color: rgba(0, 123, 255, 0.1);
    color: #007bff;
}

.notification-bell .notification-count {
    position: absolute;
    top: -2px;
    right: -2px;
    background: #dc3545;
    color: white;
    font-size: 10px;
    font-weight: bold;
    padding: 2px 4px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
    line-height: 1;
}

/* Notification Dropdown */
.notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 350px;
    max-height: 400px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    display: none;
    overflow: hidden;
}

.notification-dropdown.show {
    display: block;
    animation: slideDown 0.3s ease;
}

.notification-dropdown .dropdown-header {
    padding: 12px 16px;
    border-bottom: 1px solid #dee2e6;
    background: #f8f9fa;
    font-weight: 600;
    color: #495057;
}

.notification-dropdown .dropdown-content {
    max-height: 300px;
    overflow-y: auto;
}

.notification-dropdown .dropdown-footer {
    padding: 12px 16px;
    border-top: 1px solid #dee2e6;
    background: #f8f9fa;
}

/* Notification Items */
.notification-item {
    display: flex;
    align-items: flex-start;
    padding: 12px 16px;
    border-bottom: 1px solid #f1f3f4;
    transition: background-color 0.2s ease;
    cursor: pointer;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #f0f8ff;
    border-left: 3px solid #007bff;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-icon {
    margin-right: 12px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.notification-icon i {
    font-size: 16px;
}

.notification-item.unread .notification-icon {
    background: rgba(0, 123, 255, 0.1);
}

.notification-item.read .notification-icon {
    background: rgba(108, 117, 125, 0.1);
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-title {
    font-weight: 600;
    color: #212529;
    margin-bottom: 4px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.notification-message {
    font-size: 14px;
    color: #6c757d;
    margin-bottom: 4px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.notification-time {
    font-size: 12px;
    color: #adb5bd;
}

.notification-actions {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-left: 8px;
}

.notification-actions .btn {
    padding: 4px 8px;
    font-size: 12px;
    border-radius: 4px;
}

/* Notification Toast */
.notification-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    z-index: 9999;
    min-width: 300px;
    max-width: 400px;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
}

.notification-toast.show {
    opacity: 1;
    transform: translateX(0);
}

.toast-content {
    display: flex;
    align-items: flex-start;
    padding: 16px;
}

.toast-icon {
    margin-right: 12px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.toast-icon i {
    font-size: 16px;
}

.toast-message {
    flex: 1;
    min-width: 0;
}

.toast-title {
    font-weight: 600;
    color: #212529;
    margin-bottom: 4px;
}

.toast-text {
    font-size: 14px;
    color: #6c757d;
}

.toast-actions {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-left: 12px;
}

.toast-actions .btn {
    padding: 4px 8px;
    font-size: 12px;
    border-radius: 4px;
}

/* Notification Settings */
.notification-settings {
    max-width: 800px;
    margin: 0 auto;
}

.notification-settings .setting-card {
    background: white;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.notification-settings .setting-card h3 {
    color: #212529;
    margin-bottom: 16px;
    font-size: 18px;
}

.notification-settings .setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 0;
    border-bottom: 1px solid #f1f3f4;
}

.notification-settings .setting-item:last-child {
    border-bottom: none;
}

.notification-settings .setting-info h4 {
    color: #212529;
    margin-bottom: 4px;
    font-size: 16px;
}

.notification-settings .setting-info p {
    color: #6c757d;
    font-size: 14px;
    margin: 0;
}

.notification-settings .setting-controls {
    display: flex;
    align-items: center;
    gap: 16px;
}

.notification-settings .form-check {
    margin: 0;
}

.notification-settings .form-check-input {
    margin-right: 8px;
}

.notification-settings .form-check-label {
    color: #495057;
    font-size: 14px;
    cursor: pointer;
}

/* Notification Preferences */
.notification-preferences {
    max-width: 900px;
    margin: 0 auto;
}

.notification-preferences .preference-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.notification-preferences .preference-table table {
    width: 100%;
    border-collapse: collapse;
}

.notification-preferences .preference-table th {
    background: #f8f9fa;
    padding: 16px;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 1px solid #dee2e6;
}

.notification-preferences .preference-table td {
    padding: 16px;
    border-bottom: 1px solid #f1f3f4;
    vertical-align: middle;
}

.notification-preferences .preference-table tr:last-child td {
    border-bottom: none;
}

.notification-preferences .preference-type {
    font-weight: 600;
    color: #212529;
}

.notification-preferences .preference-description {
    color: #6c757d;
    font-size: 14px;
    margin-top: 4px;
}

.notification-preferences .channel-options {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.notification-preferences .channel-option {
    display: flex;
    align-items: center;
    gap: 4px;
}

.notification-preferences .channel-option input[type="checkbox"] {
    margin: 0;
}

.notification-preferences .channel-option label {
    font-size: 14px;
    color: #495057;
    cursor: pointer;
}

/* Animations */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
    }
    to {
        transform: translateX(0);
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .notification-dropdown {
        width: 300px;
        right: -10px;
    }

    .notification-toast {
        right: 10px;
        left: 10px;
        max-width: none;
    }

    .notification-settings .setting-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .notification-settings .setting-controls {
        width: 100%;
        justify-content: space-between;
    }

    .notification-preferences .preference-table {
        font-size: 14px;
    }

    .notification-preferences .preference-table th,
    .notification-preferences .preference-table td {
        padding: 12px 8px;
    }
}

@media (max-width: 480px) {
    .notification-dropdown {
        width: calc(100vw - 20px);
        right: 10px;
    }

    .notification-toast {
        right: 10px;
        left: 10px;
        max-width: none;
    }

    .notification-settings .setting-card {
        padding: 16px;
    }

    .notification-preferences .preference-table {
        font-size: 12px;
    }

    .notification-preferences .preference-table th,
    .notification-preferences .preference-table td {
        padding: 8px 4px;
    }
}
</style>
