# Advanced Analytics Dashboards - User Role Permissions Matrix

## Overview
This document defines the role-based access control (RBAC) matrix for the Advanced Analytics Dashboards feature. The permissions system ensures that users can only access data and functionality appropriate to their role and responsibilities.

## User Roles Hierarchy

### System Roles
```
Super Admin
├── System Admin
│   ├── Hospital Admin
│   │   ├── Department Head
│   │   │   ├── Senior Doctor
│   │   │   │   ├── Doctor
│   │   │   │   │   ├── Nurse
│   │   │   │   │   │   ├── Medical Assistant
│   │   │   │   │   │   │   └── Receptionist
│   │   │   │   └── Resident
│   │   │   └── Fellow
│   │   └── Practice Manager
│   └── Billing Manager
└── Compliance Officer
```

### Role Definitions

#### Super Admin
- **Description**: Complete system access across all hospitals
- **Scope**: Multi-tenant, system-wide analytics
- **Responsibilities**: System monitoring, compliance oversight, strategic analytics

#### System Admin
- **Description**: Administrative access to system operations
- **Scope**: All hospitals within the system
- **Responsibilities**: System performance, user management, cross-hospital analytics

#### Hospital Admin
- **Description**: Administrative control over a specific hospital
- **Scope**: Single hospital, all departments
- **Responsibilities**: Hospital performance, resource allocation, quality metrics

#### Department Head
- **Description**: Leadership role within a specific department
- **Scope**: Single department within a hospital
- **Responsibilities**: Department performance, staff management, clinical outcomes

#### Senior Doctor
- **Description**: Experienced physician with supervisory responsibilities
- **Scope**: Individual practice with team oversight
- **Responsibilities**: Patient care quality, team performance, clinical metrics

#### Doctor
- **Description**: Licensed physician
- **Scope**: Individual patient care and practice metrics
- **Responsibilities**: Patient outcomes, personal performance metrics

#### Nurse
- **Description**: Registered nursing professional
- **Scope**: Patient care coordination and clinical support
- **Responsibilities**: Patient care quality, workflow efficiency

#### Medical Assistant
- **Description**: Clinical support staff
- **Scope**: Administrative and clinical assistance
- **Responsibilities**: Appointment coordination, basic clinical metrics

#### Receptionist
- **Description**: Front desk and administrative support
- **Scope**: Patient intake and scheduling
- **Responsibilities**: Appointment efficiency, patient satisfaction

#### Practice Manager
- **Description**: Administrative management of medical practice
- **Scope**: Practice operations and business metrics
- **Responsibilities**: Operational efficiency, financial performance

#### Billing Manager
- **Description**: Revenue cycle management
- **Scope**: Financial operations and billing analytics
- **Responsibilities**: Revenue optimization, payment analytics

#### Compliance Officer
- **Description**: Regulatory compliance and audit oversight
- **Scope**: Compliance metrics and audit trails
- **Responsibilities**: Regulatory compliance, risk management

## Permissions Matrix

### Dashboard Access Levels

| Permission | Super Admin | System Admin | Hospital Admin | Dept Head | Senior Doctor | Doctor | Nurse | Med Asst | Receptionist | Practice Mgr | Billing Mgr | Compliance |
|------------|-------------|--------------|----------------|-----------|---------------|--------|-------|----------|--------------|--------------|-------------|-------------|
| **Executive Dashboard** | Full | Full | Hospital | Dept | Team | Personal | Limited | Limited | Basic | Full | Revenue | Compliance |
| **Revenue Dashboard** | Full | Full | Hospital | Dept | None | None | None | None | None | Full | Full | None |
| **Patient Experience** | Full | Full | Hospital | Dept | Team | Personal | Full | Limited | Basic | Full | None | Limited |
| **Operations Dashboard** | Full | Full | Hospital | Dept | Team | Limited | Full | Limited | Basic | Full | Limited | Limited |
| **Clinical Dashboard** | Full | Full | Hospital | Dept | Team | Full | Limited | None | None | Limited | None | Full |

### Data Access Scopes

#### Row-Level Security (RLS) Filters

| Data Type | Super Admin | System Admin | Hospital Admin | Dept Head | Senior Doctor | Doctor | Nurse | Med Asst | Receptionist | Practice Mgr | Billing Mgr | Compliance |
|-----------|-------------|--------------|----------------|-----------|---------------|--------|-------|----------|--------------|--------------|-------------|-------------|
| **Patient Data** | All | All Hospitals | Hospital | Department | Assigned | Assigned | Assigned | Assigned | None | Hospital | None | Audit Only |
| **Financial Data** | All | All Hospitals | Hospital | Department | None | None | None | None | None | Hospital | Hospital | None |
| **Clinical Outcomes** | All | All Hospitals | Hospital | Department | Team | Personal | Team | None | None | Hospital | None | Hospital |
| **Staff Performance** | All | All Hospitals | Hospital | Department | Team | Personal | Team | None | None | Hospital | None | Hospital |
| **System Metrics** | All | All Hospitals | Hospital | Department | None | None | None | None | None | Hospital | None | All |

### Feature Permissions

#### Dashboard Features

| Feature | Super Admin | System Admin | Hospital Admin | Dept Head | Senior Doctor | Doctor | Nurse | Med Asst | Receptionist | Practice Mgr | Billing Mgr | Compliance |
|---------|-------------|--------------|----------------|-----------|---------------|--------|-------|----------|--------------|--------------|-------------|-------------|
| **Real-time Updates** | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| **Export Data** | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ | ✓ | ✓ | ✓ |
| **Custom Date Ranges** | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| **Drill-down Analysis** | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | Limited | Limited | Limited | ✓ | ✓ | ✓ |
| **Alert Configuration** | ✓ | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | ✓ | ✓ | ✓ |
| **Dashboard Customization** | ✓ | ✓ | ✓ | ✓ | ✓ | Limited | Limited | Limited | Limited | ✓ | ✓ | ✓ |
| **Scheduled Reports** | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ | ✓ | ✓ | ✓ |
| **API Access** | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ | ✓ | ✓ | ✓ |

#### KPI Access Matrix

| KPI Category | Super Admin | System Admin | Hospital Admin | Dept Head | Senior Doctor | Doctor | Nurse | Med Asst | Receptionist | Practice Mgr | Billing Mgr | Compliance |
|--------------|-------------|--------------|----------------|-----------|---------------|--------|-------|----------|--------------|--------------|-------------|-------------|
| **Revenue Metrics** | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ | ✓ | ✓ | ✗ |
| **Patient Satisfaction** | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✗ | ✓ |
| **Operational Efficiency** | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| **Clinical Outcomes** | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ | ✓ | ✗ | ✓ |
| **Quality Measures** | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ | ✓ | ✗ | ✓ |
| **Compliance Metrics** | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ | ✓ | ✗ | ✓ |
| **Staff Performance** | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ | ✓ | ✗ | ✓ |
| **System Performance** | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ | ✗ | ✓ | ✗ | ✓ |

### Permission Implementation

#### Database Schema for Permissions

```sql
-- User roles table
CREATE TABLE analytics_roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    role_description TEXT,
    hierarchy_level INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Dashboard permissions table
CREATE TABLE dashboard_permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    dashboard_name VARCHAR(100) NOT NULL,
    access_level ENUM('none', 'basic', 'limited', 'full') NOT NULL,
    data_scope ENUM('personal', 'team', 'department', 'hospital', 'system') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES analytics_roles(role_id)
);

-- Feature permissions table
CREATE TABLE feature_permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    feature_name VARCHAR(100) NOT NULL,
    can_access BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES analytics_roles(role_id)
);

-- KPI permissions table
CREATE TABLE kpi_permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    kpi_category VARCHAR(100) NOT NULL,
    can_view BOOLEAN DEFAULT FALSE,
    can_export BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES analytics_roles(role_id)
);
```

#### Permission Checking Logic

```php
class AnalyticsPermissions
{
    public function canAccessDashboard(User $user, string $dashboard): bool
    {
        $role = $user->getAnalyticsRole();
        $permission = DashboardPermission::where('role_id', $role->id)
            ->where('dashboard_name', $dashboard)
            ->first();

        return $permission && $permission->access_level !== 'none';
    }

    public function getDataScope(User $user, string $dashboard): string
    {
        $role = $user->getAnalyticsRole();
        $permission = DashboardPermission::where('role_id', $role->id)
            ->where('dashboard_name', $dashboard)
            ->first();

        return $permission ? $permission->data_scope : 'none';
    }

    public function canViewKPI(User $user, string $kpiCategory): bool
    {
        $role = $user->getAnalyticsRole();
        $permission = KpiPermission::where('role_id', $role->id)
            ->where('kpi_category', $kpiCategory)
            ->first();

        return $permission ? $permission->can_view : false;
    }

    public function applyDataFilter(User $user, Builder $query): Builder
    {
        $scope = $this->getDataScope($user, 'current_dashboard');

        switch ($scope) {
            case 'personal':
                return $query->where('user_id', $user->id);
            case 'team':
                return $query->where('team_id', $user->team_id);
            case 'department':
                return $query->where('department_id', $user->department_id);
            case 'hospital':
                return $query->where('hospital_id', $user->hospital_id);
            case 'system':
                return $query; // No filter
            default:
                return $query->whereRaw('1 = 0'); // No access
        }
    }
}
```

### Security Considerations

#### Data Privacy
- **HIPAA Compliance**: Patient data access restricted to authorized personnel
- **Audit Logging**: All dashboard access and data exports logged
- **Data Masking**: Sensitive information masked for unauthorized users
- **Access Reviews**: Regular review of user permissions

#### Authentication
- **Multi-Factor Authentication**: Required for admin roles
- **Session Management**: Automatic logout after inactivity
- **IP Restrictions**: Geographic and IP-based access controls
- **Device Management**: Authorized device registration

#### Monitoring and Auditing
- **Access Logs**: Detailed logging of all dashboard interactions
- **Permission Changes**: Audit trail for role and permission modifications
- **Anomaly Detection**: Unusual access patterns flagged for review
- **Compliance Reports**: Regular reports for regulatory requirements

### Permission Management Interface

#### Admin Dashboard for Permissions
- **Role Management**: Create, modify, and delete roles
- **Permission Assignment**: Assign permissions to roles
- **User Role Assignment**: Assign roles to users
- **Bulk Operations**: Mass permission updates
- **Audit Trail**: View permission change history

#### User Self-Service
- **Permission Requests**: Users can request additional permissions
- **Access Reviews**: Regular review of current permissions
- **Training Requirements**: Permission-based training modules
- **Compliance Acknowledgments**: Required compliance training

### Implementation Guidelines

#### Permission Caching
- **Redis Caching**: User permissions cached for performance
- **Cache Invalidation**: Automatic invalidation on permission changes
- **Fallback Logic**: Database fallback if cache unavailable

#### Performance Optimization
- **Permission Preloading**: Load user permissions on login
- **Batch Permission Checks**: Multiple permissions checked in single query
- **Index Optimization**: Database indexes on frequently queried columns

#### Testing Strategy
- **Unit Tests**: Individual permission functions
- **Integration Tests**: End-to-end permission workflows
- **Security Tests**: Penetration testing for permission bypass
- **Performance Tests**: Permission checking under load

### Migration Strategy

#### Phase 1: Foundation
- Create permission tables and seed initial roles
- Implement basic permission checking middleware
- Add permission management interface

#### Phase 2: Integration
- Integrate permissions with existing dashboards
- Implement data filtering based on permissions
- Add audit logging for permission checks

#### Phase 3: Enhancement
- Add advanced permission features (time-based, location-based)
- Implement permission delegation
- Add automated permission reviews

#### Phase 4: Optimization
- Performance optimization and caching
- Advanced security features
- Comprehensive testing and documentation
