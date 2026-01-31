# Job Platform Use Case Diagram - Mermaid (Corrected)

```mermaid
graph TD
    %% Actors
    JobSeeker((باحث عن عمل))
    Employer((صاحب عمل))
    Admin((المدير))
    
    %% Job Seeker Use Cases
    UC_Register[تسجيل حساب جديد]
    UC_Profile[إدارة الملف الشخصي]
    UC_SearchJobs[البحث عن وظائف]
    UC_Apply[التقديم على وظائف]
    UC_Applications[عرض الطلبات]
    
    %% Employer Use Cases
    UC_PostJob[نشر وظيفة]
    UC_ManageJobs[إدارة الوظائف]
    UC_ViewApplicants[عرض المتقدمين]
    UC_Review[مراجعة الطلبات]
    
    %% Admin Use Cases
    UC_ManageUsers[إدارة المستخدمين]
    UC_Reports[عرض التقارير]
    UC_Settings[إعدادات النظام]
    
    %% Common Use Cases
    UC_Login[تسجيل الدخول]
    UC_Logout[تسجيل الخروج]
    UC_ForgotPassword[نسيت كلمة المرور]
    UC_SaveJob[حفظ وظيفة]
    UC_PromoteJob[ترويج وظيفة]
    
    %% Relationships
    JobSeeker --> UC_Register
    JobSeeker --> UC_Login
    JobSeeker --> UC_Profile
    JobSeeker --> UC_SearchJobs
    JobSeeker --> UC_Apply
    JobSeeker --> UC_Applications
    JobSeeker --> UC_Logout
    
    Employer --> UC_Register
    Employer --> UC_Login
    Employer --> UC_PostJob
    Employer --> UC_ManageJobs
    Employer --> UC_ViewApplicants
    Employer --> UC_Review
    Employer --> UC_Logout
    
    Admin --> UC_Register
    Admin --> UC_Login
    Admin --> UC_ManageUsers
    Admin --> UC_Reports
    Admin --> UC_Settings
    Admin --> UC_Logout
    
    %% Include Relationships
    UC_Apply -.-> UC_SearchJobs
    UC_Review -.-> UC_ViewApplicants
    UC_ManageJobs -.-> UC_PostJob

    %% Extend Relationships
    UC_ForgotPassword ..> UC_Login
    UC_SaveJob ..> UC_SearchJobs
    UC_PromoteJob ..> UC_PostJob
    
    %% System Boundary
    subgraph "منصة التوظيف"
        UC_Login
        UC_Logout
        UC_Register
        UC_Profile
        UC_SearchJobs
        UC_Apply
        UC_Applications
        UC_PostJob
        UC_ManageJobs
        UC_ViewApplicants
        UC_Review
        UC_ManageUsers
        UC_Reports
        UC_Settings
        UC_ForgotPassword
        UC_SaveJob
        UC_PromoteJob
    end
    
    %% Styling
    classDef actor fill:#e1f5fe,stroke:#01579b,stroke-width:2px
    classDef usecase fill:#f3e5f5,stroke:#4a148c,stroke-width:2px
    
    class JobSeeker,Employer,Admin actor
    class UC_Login,UC_Logout,UC_Register,UC_Profile,UC_SearchJobs,UC_Apply,UC_Applications,UC_PostJob,UC_ManageJobs,UC_ViewApplicants,UC_Review,UC_ManageUsers,UC_Reports,UC_Settings,UC_ForgotPassword,UC_SaveJob,UC_PromoteJob usecase
