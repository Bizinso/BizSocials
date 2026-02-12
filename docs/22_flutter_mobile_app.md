# Flutter Mobile App Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2025-02-06
- **Platform**: iOS & Android
- **Framework**: Flutter 3.x with Dart

---

## 1. Overview

### 1.1 Purpose
Provide a native mobile experience for BizSocials users to manage their social media presence on-the-go, with focus on quick actions, notifications, and content management.

### 1.2 Target Platforms
```
┌─────────────────────────────────────────────────────────────────┐
│                    PLATFORM SUPPORT                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  iOS                                                            │
│  ├── Minimum Version: iOS 14.0                                  │
│  ├── Devices: iPhone, iPad (optimized)                          │
│  └── Distribution: App Store                                    │
│                                                                 │
│  Android                                                        │
│  ├── Minimum SDK: 24 (Android 7.0)                              │
│  ├── Target SDK: 34 (Android 14)                                │
│  └── Distribution: Google Play Store                            │
└─────────────────────────────────────────────────────────────────┘
```

### 1.3 Core Features
```
┌─────────────────────────────────────────────────────────────────┐
│                    MOBILE APP FEATURES                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ESSENTIAL (Phase 1)                                            │
│  ├── Authentication (Login, Biometrics)                         │
│  ├── Dashboard & Quick Stats                                    │
│  ├── Content Calendar View                                      │
│  ├── Create & Schedule Posts                                    │
│  ├── Social Inbox (Messages, Comments)                          │
│  ├── Push Notifications                                         │
│  └── Analytics Overview                                         │
│                                                                 │
│  ENHANCED (Phase 2)                                             │
│  ├── AI Caption Generation                                      │
│  ├── Media Library Access                                       │
│  ├── Team Collaboration                                         │
│  ├── Approval Workflows                                         │
│  ├── Detailed Analytics                                         │
│  └── Offline Mode                                               │
│                                                                 │
│  ADVANCED (Phase 3)                                             │
│  ├── Stories Creation                                           │
│  ├── Reels/Shorts Editor                                        │
│  ├── Live Streaming Integration                                 │
│  ├── Widget Support                                             │
│  └── Apple Watch/Wear OS                                        │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Architecture

### 2.1 Project Structure
```
bizsocials_mobile/
├── lib/
│   ├── main.dart
│   ├── app.dart
│   │
│   ├── core/
│   │   ├── config/
│   │   │   ├── app_config.dart
│   │   │   ├── api_config.dart
│   │   │   └── theme_config.dart
│   │   ├── constants/
│   │   │   ├── api_endpoints.dart
│   │   │   ├── app_constants.dart
│   │   │   └── storage_keys.dart
│   │   ├── errors/
│   │   │   ├── exceptions.dart
│   │   │   └── failures.dart
│   │   ├── network/
│   │   │   ├── api_client.dart
│   │   │   ├── interceptors/
│   │   │   └── network_info.dart
│   │   ├── routing/
│   │   │   ├── app_router.dart
│   │   │   └── route_guards.dart
│   │   └── utils/
│   │       ├── date_utils.dart
│   │       ├── validators.dart
│   │       └── extensions.dart
│   │
│   ├── data/
│   │   ├── datasources/
│   │   │   ├── local/
│   │   │   │   ├── auth_local_datasource.dart
│   │   │   │   ├── posts_local_datasource.dart
│   │   │   │   └── database/
│   │   │   └── remote/
│   │   │       ├── auth_remote_datasource.dart
│   │   │       ├── posts_remote_datasource.dart
│   │   │       └── inbox_remote_datasource.dart
│   │   ├── models/
│   │   │   ├── user_model.dart
│   │   │   ├── post_model.dart
│   │   │   ├── social_account_model.dart
│   │   │   └── analytics_model.dart
│   │   └── repositories/
│   │       ├── auth_repository_impl.dart
│   │       ├── posts_repository_impl.dart
│   │       └── inbox_repository_impl.dart
│   │
│   ├── domain/
│   │   ├── entities/
│   │   │   ├── user.dart
│   │   │   ├── post.dart
│   │   │   ├── social_account.dart
│   │   │   └── inbox_item.dart
│   │   ├── repositories/
│   │   │   ├── auth_repository.dart
│   │   │   ├── posts_repository.dart
│   │   │   └── inbox_repository.dart
│   │   └── usecases/
│   │       ├── auth/
│   │       ├── posts/
│   │       └── inbox/
│   │
│   ├── presentation/
│   │   ├── blocs/
│   │   │   ├── auth/
│   │   │   ├── posts/
│   │   │   ├── inbox/
│   │   │   └── analytics/
│   │   ├── pages/
│   │   │   ├── auth/
│   │   │   ├── dashboard/
│   │   │   ├── posts/
│   │   │   ├── inbox/
│   │   │   ├── calendar/
│   │   │   ├── analytics/
│   │   │   └── settings/
│   │   └── widgets/
│   │       ├── common/
│   │       ├── posts/
│   │       └── inbox/
│   │
│   └── injection/
│       └── injection_container.dart
│
├── assets/
│   ├── images/
│   ├── icons/
│   ├── fonts/
│   └── animations/
│
├── test/
│   ├── unit/
│   ├── widget/
│   └── integration/
│
├── ios/
├── android/
├── pubspec.yaml
└── analysis_options.yaml
```

### 2.2 State Management (BLoC Pattern)
```dart
// lib/presentation/blocs/auth/auth_bloc.dart

import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:freezed_annotation/freezed_annotation.dart';

part 'auth_bloc.freezed.dart';
part 'auth_event.dart';
part 'auth_state.dart';

class AuthBloc extends Bloc<AuthEvent, AuthState> {
  final LoginUseCase loginUseCase;
  final LogoutUseCase logoutUseCase;
  final GetCurrentUserUseCase getCurrentUserUseCase;
  final BiometricAuthUseCase biometricAuthUseCase;

  AuthBloc({
    required this.loginUseCase,
    required this.logoutUseCase,
    required this.getCurrentUserUseCase,
    required this.biometricAuthUseCase,
  }) : super(const AuthState.initial()) {
    on<_CheckAuth>(_onCheckAuth);
    on<_Login>(_onLogin);
    on<_BiometricLogin>(_onBiometricLogin);
    on<_Logout>(_onLogout);
  }

  Future<void> _onCheckAuth(
    _CheckAuth event,
    Emitter<AuthState> emit,
  ) async {
    emit(const AuthState.loading());

    final result = await getCurrentUserUseCase();

    result.fold(
      (failure) => emit(const AuthState.unauthenticated()),
      (user) => emit(AuthState.authenticated(user)),
    );
  }

  Future<void> _onLogin(
    _Login event,
    Emitter<AuthState> emit,
  ) async {
    emit(const AuthState.loading());

    final result = await loginUseCase(
      LoginParams(
        email: event.email,
        password: event.password,
      ),
    );

    result.fold(
      (failure) => emit(AuthState.error(failure.message)),
      (user) => emit(AuthState.authenticated(user)),
    );
  }

  Future<void> _onBiometricLogin(
    _BiometricLogin event,
    Emitter<AuthState> emit,
  ) async {
    emit(const AuthState.loading());

    final result = await biometricAuthUseCase();

    result.fold(
      (failure) => emit(AuthState.error(failure.message)),
      (user) => emit(AuthState.authenticated(user)),
    );
  }
}

// auth_event.dart
part of 'auth_bloc.dart';

@freezed
class AuthEvent with _$AuthEvent {
  const factory AuthEvent.checkAuth() = _CheckAuth;
  const factory AuthEvent.login({
    required String email,
    required String password,
  }) = _Login;
  const factory AuthEvent.biometricLogin() = _BiometricLogin;
  const factory AuthEvent.logout() = _Logout;
}

// auth_state.dart
part of 'auth_bloc.dart';

@freezed
class AuthState with _$AuthState {
  const factory AuthState.initial() = _Initial;
  const factory AuthState.loading() = _Loading;
  const factory AuthState.authenticated(User user) = _Authenticated;
  const factory AuthState.unauthenticated() = _Unauthenticated;
  const factory AuthState.error(String message) = _Error;
}
```

### 2.3 API Client
```dart
// lib/core/network/api_client.dart

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class ApiClient {
  late final Dio _dio;
  final FlutterSecureStorage _secureStorage;

  ApiClient({
    required FlutterSecureStorage secureStorage,
    required String baseUrl,
  }) : _secureStorage = secureStorage {
    _dio = Dio(
      BaseOptions(
        baseUrl: baseUrl,
        connectTimeout: const Duration(seconds: 30),
        receiveTimeout: const Duration(seconds: 30),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      ),
    );

    _dio.interceptors.addAll([
      AuthInterceptor(secureStorage: _secureStorage),
      LoggingInterceptor(),
      RetryInterceptor(dio: _dio),
    ]);
  }

  Future<Response<T>> get<T>(
    String path, {
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) {
    return _dio.get<T>(
      path,
      queryParameters: queryParameters,
      options: options,
    );
  }

  Future<Response<T>> post<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) {
    return _dio.post<T>(
      path,
      data: data,
      queryParameters: queryParameters,
      options: options,
    );
  }

  Future<Response<T>> put<T>(
    String path, {
    dynamic data,
    Options? options,
  }) {
    return _dio.put<T>(path, data: data, options: options);
  }

  Future<Response<T>> delete<T>(
    String path, {
    Options? options,
  }) {
    return _dio.delete<T>(path, options: options);
  }

  Future<Response<T>> upload<T>(
    String path, {
    required FormData formData,
    ProgressCallback? onSendProgress,
  }) {
    return _dio.post<T>(
      path,
      data: formData,
      onSendProgress: onSendProgress,
      options: Options(
        headers: {'Content-Type': 'multipart/form-data'},
      ),
    );
  }
}

// Auth Interceptor
class AuthInterceptor extends Interceptor {
  final FlutterSecureStorage _secureStorage;

  AuthInterceptor({required FlutterSecureStorage secureStorage})
      : _secureStorage = secureStorage;

  @override
  Future<void> onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    final token = await _secureStorage.read(key: StorageKeys.accessToken);

    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }

    handler.next(options);
  }

  @override
  Future<void> onError(
    DioException err,
    ErrorInterceptorHandler handler,
  ) async {
    if (err.response?.statusCode == 401) {
      // Try to refresh token
      final refreshed = await _refreshToken();

      if (refreshed) {
        // Retry the request
        final response = await _retry(err.requestOptions);
        handler.resolve(response);
        return;
      } else {
        // Clear tokens and redirect to login
        await _clearTokens();
      }
    }

    handler.next(err);
  }

  Future<bool> _refreshToken() async {
    try {
      final refreshToken = await _secureStorage.read(
        key: StorageKeys.refreshToken,
      );

      if (refreshToken == null) return false;

      final dio = Dio();
      final response = await dio.post(
        '${ApiConfig.baseUrl}/auth/refresh',
        data: {'refresh_token': refreshToken},
      );

      if (response.statusCode == 200) {
        await _secureStorage.write(
          key: StorageKeys.accessToken,
          value: response.data['access_token'],
        );
        await _secureStorage.write(
          key: StorageKeys.refreshToken,
          value: response.data['refresh_token'],
        );
        return true;
      }
    } catch (e) {
      return false;
    }
    return false;
  }

  Future<void> _clearTokens() async {
    await _secureStorage.delete(key: StorageKeys.accessToken);
    await _secureStorage.delete(key: StorageKeys.refreshToken);
  }
}
```

---

## 3. Authentication

### 3.1 Login Screen
```dart
// lib/presentation/pages/auth/login_page.dart

import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:local_auth/local_auth.dart';

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;
  bool _canCheckBiometrics = false;

  @override
  void initState() {
    super.initState();
    _checkBiometrics();
  }

  Future<void> _checkBiometrics() async {
    final localAuth = LocalAuthentication();
    final canCheck = await localAuth.canCheckBiometrics;
    final isDeviceSupported = await localAuth.isDeviceSupported();

    setState(() {
      _canCheckBiometrics = canCheck && isDeviceSupported;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: BlocConsumer<AuthBloc, AuthState>(
        listener: (context, state) {
          state.maybeWhen(
            authenticated: (user) {
              Navigator.of(context).pushReplacementNamed('/dashboard');
            },
            error: (message) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text(message)),
              );
            },
            orElse: () {},
          );
        },
        builder: (context, state) {
          return SafeArea(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const SizedBox(height: 48),

                  // Logo
                  Image.asset(
                    'assets/images/logo.png',
                    height: 60,
                  ),
                  const SizedBox(height: 48),

                  // Title
                  Text(
                    'Welcome Back',
                    style: Theme.of(context).textTheme.headlineMedium,
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Sign in to continue to BizSocials',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: Colors.grey[600],
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 32),

                  // Form
                  Form(
                    key: _formKey,
                    child: Column(
                      children: [
                        TextFormField(
                          controller: _emailController,
                          keyboardType: TextInputType.emailAddress,
                          decoration: const InputDecoration(
                            labelText: 'Email',
                            prefixIcon: Icon(Icons.email_outlined),
                          ),
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return 'Please enter your email';
                            }
                            if (!value.contains('@')) {
                              return 'Please enter a valid email';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _passwordController,
                          obscureText: _obscurePassword,
                          decoration: InputDecoration(
                            labelText: 'Password',
                            prefixIcon: const Icon(Icons.lock_outlined),
                            suffixIcon: IconButton(
                              icon: Icon(
                                _obscurePassword
                                    ? Icons.visibility_off
                                    : Icons.visibility,
                              ),
                              onPressed: () {
                                setState(() {
                                  _obscurePassword = !_obscurePassword;
                                });
                              },
                            ),
                          ),
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return 'Please enter your password';
                            }
                            return null;
                          },
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 8),

                  // Forgot Password
                  Align(
                    alignment: Alignment.centerRight,
                    child: TextButton(
                      onPressed: () {
                        Navigator.pushNamed(context, '/forgot-password');
                      },
                      child: const Text('Forgot Password?'),
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Login Button
                  ElevatedButton(
                    onPressed: state.maybeWhen(
                      loading: () => null,
                      orElse: () => _handleLogin,
                    ),
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 16),
                    ),
                    child: state.maybeWhen(
                      loading: () => const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      ),
                      orElse: () => const Text('Sign In'),
                    ),
                  ),

                  // Biometric Login
                  if (_canCheckBiometrics) ...[
                    const SizedBox(height: 24),
                    const Row(
                      children: [
                        Expanded(child: Divider()),
                        Padding(
                          padding: EdgeInsets.symmetric(horizontal: 16),
                          child: Text('or'),
                        ),
                        Expanded(child: Divider()),
                      ],
                    ),
                    const SizedBox(height: 24),
                    OutlinedButton.icon(
                      onPressed: () {
                        context.read<AuthBloc>().add(
                          const AuthEvent.biometricLogin(),
                        );
                      },
                      icon: const Icon(Icons.fingerprint),
                      label: const Text('Sign in with Biometrics'),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  void _handleLogin() {
    if (_formKey.currentState!.validate()) {
      context.read<AuthBloc>().add(
        AuthEvent.login(
          email: _emailController.text,
          password: _passwordController.text,
        ),
      );
    }
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }
}
```

### 3.2 Biometric Authentication
```dart
// lib/domain/usecases/auth/biometric_auth_usecase.dart

import 'package:local_auth/local_auth.dart';
import 'package:dartz/dartz.dart';

class BiometricAuthUseCase {
  final LocalAuthentication _localAuth;
  final AuthRepository _authRepository;
  final FlutterSecureStorage _secureStorage;

  BiometricAuthUseCase({
    required LocalAuthentication localAuth,
    required AuthRepository authRepository,
    required FlutterSecureStorage secureStorage,
  })  : _localAuth = localAuth,
        _authRepository = authRepository,
        _secureStorage = secureStorage;

  Future<Either<Failure, User>> call() async {
    try {
      // Check if biometric credentials exist
      final storedEmail = await _secureStorage.read(
        key: StorageKeys.biometricEmail,
      );
      final storedToken = await _secureStorage.read(
        key: StorageKeys.biometricToken,
      );

      if (storedEmail == null || storedToken == null) {
        return Left(BiometricNotSetupFailure());
      }

      // Authenticate with biometrics
      final authenticated = await _localAuth.authenticate(
        localizedReason: 'Authenticate to sign in to BizSocials',
        options: const AuthenticationOptions(
          stickyAuth: true,
          biometricOnly: true,
        ),
      );

      if (!authenticated) {
        return Left(BiometricAuthFailure());
      }

      // Use stored credentials to authenticate
      return _authRepository.loginWithToken(storedToken);
    } catch (e) {
      return Left(BiometricAuthFailure(message: e.toString()));
    }
  }

  Future<bool> setupBiometrics(String email, String token) async {
    try {
      final authenticated = await _localAuth.authenticate(
        localizedReason: 'Enable biometric login for BizSocials',
        options: const AuthenticationOptions(
          stickyAuth: true,
          biometricOnly: true,
        ),
      );

      if (authenticated) {
        await _secureStorage.write(
          key: StorageKeys.biometricEmail,
          value: email,
        );
        await _secureStorage.write(
          key: StorageKeys.biometricToken,
          value: token,
        );
        return true;
      }
    } catch (e) {
      return false;
    }
    return false;
  }
}
```

---

## 4. Dashboard

### 4.1 Dashboard Screen
```dart
// lib/presentation/pages/dashboard/dashboard_page.dart

class DashboardPage extends StatelessWidget {
  const DashboardPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Dashboard'),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_outlined),
            onPressed: () => Navigator.pushNamed(context, '/notifications'),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          context.read<DashboardBloc>().add(const DashboardEvent.refresh());
        },
        child: BlocBuilder<DashboardBloc, DashboardState>(
          builder: (context, state) {
            return state.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              loaded: (data) => _buildDashboard(context, data),
              error: (message) => ErrorView(message: message),
            );
          },
        ),
      ),
      bottomNavigationBar: const AppBottomNavigation(currentIndex: 0),
    );
  }

  Widget _buildDashboard(BuildContext context, DashboardData data) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Quick Stats
          _buildQuickStats(data.stats),
          const SizedBox(height: 24),

          // Quick Actions
          _buildQuickActions(context),
          const SizedBox(height: 24),

          // Scheduled Posts
          _buildScheduledPosts(context, data.scheduledPosts),
          const SizedBox(height: 24),

          // Recent Activity
          _buildRecentActivity(data.recentActivity),
          const SizedBox(height: 24),

          // Inbox Summary
          _buildInboxSummary(context, data.inboxSummary),
        ],
      ),
    );
  }

  Widget _buildQuickStats(DashboardStats stats) {
    return Row(
      children: [
        Expanded(
          child: StatCard(
            title: 'Total Reach',
            value: formatNumber(stats.totalReach),
            change: stats.reachChange,
            icon: Icons.visibility_outlined,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: StatCard(
            title: 'Engagement',
            value: formatNumber(stats.totalEngagement),
            change: stats.engagementChange,
            icon: Icons.favorite_outline,
          ),
        ),
      ],
    );
  }

  Widget _buildQuickActions(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Quick Actions',
          style: Theme.of(context).textTheme.titleMedium,
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: QuickActionButton(
                icon: Icons.add_circle_outline,
                label: 'New Post',
                onTap: () => Navigator.pushNamed(context, '/posts/create'),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: QuickActionButton(
                icon: Icons.calendar_today_outlined,
                label: 'Calendar',
                onTap: () => Navigator.pushNamed(context, '/calendar'),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: QuickActionButton(
                icon: Icons.inbox_outlined,
                label: 'Inbox',
                badgeCount: 5,
                onTap: () => Navigator.pushNamed(context, '/inbox'),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: QuickActionButton(
                icon: Icons.analytics_outlined,
                label: 'Analytics',
                onTap: () => Navigator.pushNamed(context, '/analytics'),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildScheduledPosts(BuildContext context, List<Post> posts) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              'Upcoming Posts',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            TextButton(
              onPressed: () => Navigator.pushNamed(context, '/calendar'),
              child: const Text('See All'),
            ),
          ],
        ),
        const SizedBox(height: 12),
        if (posts.isEmpty)
          const EmptyState(
            icon: Icons.schedule,
            message: 'No scheduled posts',
          )
        else
          ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: posts.take(3).length,
            separatorBuilder: (_, __) => const SizedBox(height: 8),
            itemBuilder: (context, index) {
              return ScheduledPostCard(post: posts[index]);
            },
          ),
      ],
    );
  }
}
```

---

## 5. Post Creation

### 5.1 Create Post Screen
```dart
// lib/presentation/pages/posts/create_post_page.dart

class CreatePostPage extends StatefulWidget {
  const CreatePostPage({super.key});

  @override
  State<CreatePostPage> createState() => _CreatePostPageState();
}

class _CreatePostPageState extends State<CreatePostPage> {
  final _contentController = TextEditingController();
  final List<XFile> _selectedMedia = [];
  final Set<String> _selectedAccounts = {};
  DateTime? _scheduledTime;
  bool _isScheduled = false;

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => getIt<CreatePostBloc>(),
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Create Post'),
          actions: [
            BlocBuilder<CreatePostBloc, CreatePostState>(
              builder: (context, state) {
                return TextButton(
                  onPressed: state.maybeWhen(
                    saving: () => null,
                    orElse: () => _handlePublish,
                  ),
                  child: state.maybeWhen(
                    saving: () => const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    ),
                    orElse: () => Text(
                      _isScheduled ? 'Schedule' : 'Post Now',
                    ),
                  ),
                );
              },
            ),
          ],
        ),
        body: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Account Selection
              _buildAccountSelection(),
              const SizedBox(height: 16),

              // Content Input
              _buildContentInput(),
              const SizedBox(height: 16),

              // Media Selection
              _buildMediaSection(),
              const SizedBox(height: 16),

              // AI Tools
              _buildAITools(),
              const SizedBox(height: 16),

              // Scheduling
              _buildSchedulingSection(),
              const SizedBox(height: 16),

              // Preview
              _buildPreview(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildAccountSelection() {
    return BlocBuilder<SocialAccountsBloc, SocialAccountsState>(
      builder: (context, state) {
        return state.when(
          loading: () => const CircularProgressIndicator(),
          loaded: (accounts) {
            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Post to',
                  style: Theme.of(context).textTheme.titleSmall,
                ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: accounts.map((account) {
                    final isSelected = _selectedAccounts.contains(account.id);
                    return FilterChip(
                      avatar: CircleAvatar(
                        backgroundImage: NetworkImage(account.profileImageUrl),
                      ),
                      label: Text(account.name),
                      selected: isSelected,
                      onSelected: (selected) {
                        setState(() {
                          if (selected) {
                            _selectedAccounts.add(account.id);
                          } else {
                            _selectedAccounts.remove(account.id);
                          }
                        });
                      },
                    );
                  }).toList(),
                ),
              ],
            );
          },
          error: (message) => ErrorView(message: message),
        );
      },
    );
  }

  Widget _buildContentInput() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        TextField(
          controller: _contentController,
          maxLines: 6,
          maxLength: 3000,
          decoration: InputDecoration(
            hintText: "What's on your mind?",
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
            ),
          ),
          onChanged: (_) => setState(() {}),
        ),
        const SizedBox(height: 8),
        // Character count per platform
        _buildCharacterCounts(),
      ],
    );
  }

  Widget _buildCharacterCounts() {
    final length = _contentController.text.length;

    return Wrap(
      spacing: 8,
      children: [
        _buildCharacterCount('Twitter', length, 280),
        _buildCharacterCount('LinkedIn', length, 3000),
        _buildCharacterCount('Instagram', length, 2200),
      ],
    );
  }

  Widget _buildCharacterCount(String platform, int current, int max) {
    final isOver = current > max;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: isOver ? Colors.red.shade50 : Colors.grey.shade100,
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(
        '$platform: $current/$max',
        style: TextStyle(
          fontSize: 12,
          color: isOver ? Colors.red : Colors.grey.shade700,
        ),
      ),
    );
  }

  Widget _buildMediaSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              'Media',
              style: Theme.of(context).textTheme.titleSmall,
            ),
            Row(
              children: [
                IconButton(
                  icon: const Icon(Icons.photo_library_outlined),
                  onPressed: _pickFromGallery,
                  tooltip: 'Pick from gallery',
                ),
                IconButton(
                  icon: const Icon(Icons.camera_alt_outlined),
                  onPressed: _takePhoto,
                  tooltip: 'Take photo',
                ),
              ],
            ),
          ],
        ),
        if (_selectedMedia.isNotEmpty) ...[
          const SizedBox(height: 8),
          SizedBox(
            height: 100,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: _selectedMedia.length,
              separatorBuilder: (_, __) => const SizedBox(width: 8),
              itemBuilder: (context, index) {
                return MediaThumbnail(
                  file: _selectedMedia[index],
                  onRemove: () {
                    setState(() {
                      _selectedMedia.removeAt(index);
                    });
                  },
                );
              },
            ),
          ),
        ],
      ],
    );
  }

  Widget _buildAITools() {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.blue.shade50,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.auto_awesome, color: Colors.blue.shade700),
              const SizedBox(width: 8),
              Text(
                'AI Tools',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: Colors.blue.shade700,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              ActionChip(
                avatar: const Icon(Icons.edit, size: 18),
                label: const Text('Generate Caption'),
                onPressed: _generateCaption,
              ),
              ActionChip(
                avatar: const Icon(Icons.tag, size: 18),
                label: const Text('Suggest Hashtags'),
                onPressed: _suggestHashtags,
              ),
              ActionChip(
                avatar: const Icon(Icons.schedule, size: 18),
                label: const Text('Best Time'),
                onPressed: _suggestBestTime,
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSchedulingSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SwitchListTile(
          title: const Text('Schedule for later'),
          value: _isScheduled,
          onChanged: (value) {
            setState(() {
              _isScheduled = value;
              if (!value) {
                _scheduledTime = null;
              }
            });
          },
        ),
        if (_isScheduled) ...[
          const SizedBox(height: 8),
          ListTile(
            leading: const Icon(Icons.calendar_today),
            title: Text(
              _scheduledTime != null
                  ? formatDateTime(_scheduledTime!)
                  : 'Select date and time',
            ),
            trailing: const Icon(Icons.chevron_right),
            onTap: _pickDateTime,
          ),
        ],
      ],
    );
  }

  Future<void> _generateCaption() async {
    final result = await showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      builder: (_) => AICaptionGeneratorSheet(
        currentContent: _contentController.text,
        selectedAccounts: _selectedAccounts.toList(),
      ),
    );

    if (result != null) {
      _contentController.text = result;
    }
  }
}
```

---

## 6. Push Notifications

### 6.1 Notification Service
```dart
// lib/core/services/notification_service.dart

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

class NotificationService {
  final FirebaseMessaging _firebaseMessaging;
  final FlutterLocalNotificationsPlugin _localNotifications;

  NotificationService({
    required FirebaseMessaging firebaseMessaging,
    required FlutterLocalNotificationsPlugin localNotifications,
  })  : _firebaseMessaging = firebaseMessaging,
        _localNotifications = localNotifications;

  Future<void> initialize() async {
    // Request permissions
    await _firebaseMessaging.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );

    // Configure local notifications
    const initializationSettingsAndroid = AndroidInitializationSettings(
      '@mipmap/ic_launcher',
    );
    const initializationSettingsIOS = DarwinInitializationSettings(
      requestAlertPermission: true,
      requestBadgePermission: true,
      requestSoundPermission: true,
    );
    const initializationSettings = InitializationSettings(
      android: initializationSettingsAndroid,
      iOS: initializationSettingsIOS,
    );

    await _localNotifications.initialize(
      initializationSettings,
      onDidReceiveNotificationResponse: _onNotificationTap,
    );

    // Create notification channels (Android)
    await _createNotificationChannels();

    // Handle foreground messages
    FirebaseMessaging.onMessage.listen(_handleForegroundMessage);

    // Handle background message tap
    FirebaseMessaging.onMessageOpenedApp.listen(_handleNotificationTap);

    // Check for initial message (app opened from terminated state)
    final initialMessage = await _firebaseMessaging.getInitialMessage();
    if (initialMessage != null) {
      _handleNotificationTap(initialMessage);
    }
  }

  Future<String?> getToken() async {
    return await _firebaseMessaging.getToken();
  }

  Future<void> subscribeToTopic(String topic) async {
    await _firebaseMessaging.subscribeToTopic(topic);
  }

  Future<void> unsubscribeFromTopic(String topic) async {
    await _firebaseMessaging.unsubscribeFromTopic(topic);
  }

  Future<void> _createNotificationChannels() async {
    const channels = [
      AndroidNotificationChannel(
        'inbox',
        'Inbox Notifications',
        description: 'New messages and comments',
        importance: Importance.high,
      ),
      AndroidNotificationChannel(
        'posts',
        'Post Notifications',
        description: 'Post published and scheduled reminders',
        importance: Importance.defaultImportance,
      ),
      AndroidNotificationChannel(
        'approvals',
        'Approval Notifications',
        description: 'Posts pending approval',
        importance: Importance.high,
      ),
      AndroidNotificationChannel(
        'analytics',
        'Analytics Notifications',
        description: 'Weekly reports and insights',
        importance: Importance.low,
      ),
    ];

    for (final channel in channels) {
      await _localNotifications
          .resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin>()
          ?.createNotificationChannel(channel);
    }
  }

  Future<void> _handleForegroundMessage(RemoteMessage message) async {
    final notification = message.notification;
    final data = message.data;

    if (notification != null) {
      await _localNotifications.show(
        notification.hashCode,
        notification.title,
        notification.body,
        NotificationDetails(
          android: AndroidNotificationDetails(
            data['channel'] ?? 'default',
            data['channel_name'] ?? 'Default',
            icon: '@mipmap/ic_launcher',
          ),
          iOS: const DarwinNotificationDetails(),
        ),
        payload: jsonEncode(data),
      );
    }
  }

  void _onNotificationTap(NotificationResponse response) {
    if (response.payload != null) {
      final data = jsonDecode(response.payload!) as Map<String, dynamic>;
      _navigateToScreen(data);
    }
  }

  void _handleNotificationTap(RemoteMessage message) {
    _navigateToScreen(message.data);
  }

  void _navigateToScreen(Map<String, dynamic> data) {
    final type = data['type'];
    final id = data['id'];

    switch (type) {
      case 'inbox':
        NavigationService.navigateTo('/inbox/$id');
        break;
      case 'post':
        NavigationService.navigateTo('/posts/$id');
        break;
      case 'approval':
        NavigationService.navigateTo('/approvals/$id');
        break;
      default:
        NavigationService.navigateTo('/dashboard');
    }
  }
}
```

### 6.2 Notification Types
```
┌─────────────────────────────────────────────────────────────────┐
│              PUSH NOTIFICATION TYPES                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  INBOX                                                          │
│  ├── New comment on your post                                   │
│  ├── New direct message                                         │
│  ├── New mention                                                │
│  └── Reply to your comment                                      │
│                                                                 │
│  POSTS                                                          │
│  ├── Post published successfully                                │
│  ├── Post failed to publish                                     │
│  ├── Scheduled post reminder (15 min before)                    │
│  └── Post performing well (engagement spike)                    │
│                                                                 │
│  APPROVALS                                                      │
│  ├── New post awaiting your approval                            │
│  ├── Your post was approved                                     │
│  ├── Your post was rejected (with reason)                       │
│  └── Approval deadline approaching                              │
│                                                                 │
│  TEAM                                                           │
│  ├── New team member joined                                     │
│  ├── You were mentioned in a comment                            │
│  └── Task assigned to you                                       │
│                                                                 │
│  ANALYTICS (Weekly Digest)                                      │
│  ├── Weekly performance summary                                 │
│  └── Notable achievements                                       │
│                                                                 │
│  SYSTEM                                                         │
│  ├── Social account disconnected                                │
│  ├── Token refresh required                                     │
│  └── Subscription renewal reminder                              │
└─────────────────────────────────────────────────────────────────┘
```

---

## 7. Offline Support

### 7.1 Offline Storage
```dart
// lib/core/database/app_database.dart

import 'package:drift/drift.dart';
import 'package:drift/native.dart';
import 'package:path_provider/path_provider.dart';
import 'package:path/path.dart' as p;

part 'app_database.g.dart';

class CachedPosts extends Table {
  IntColumn get id => integer().autoIncrement()();
  TextColumn get remoteId => text()();
  TextColumn get content => text()();
  TextColumn get mediaUrls => text().nullable()();
  TextColumn get status => text()();
  DateTimeColumn get scheduledAt => dateTime().nullable()();
  DateTimeColumn get cachedAt => dateTime()();
  BoolColumn get pendingSync => boolean().withDefault(const Constant(false))();
}

class CachedInboxItems extends Table {
  IntColumn get id => integer().autoIncrement()();
  TextColumn get remoteId => text()();
  TextColumn get platform => text()();
  TextColumn get type => text()();
  TextColumn get content => text()();
  TextColumn get senderName => text()();
  TextColumn get senderAvatar => text().nullable()();
  BoolColumn get isRead => boolean()();
  DateTimeColumn get receivedAt => dateTime()();
  DateTimeColumn get cachedAt => dateTime()();
}

class DraftPosts extends Table {
  IntColumn get id => integer().autoIncrement()();
  TextColumn get content => text()();
  TextColumn get mediaLocalPaths => text().nullable()();
  TextColumn get selectedAccounts => text()();
  DateTimeColumn get scheduledAt => dateTime().nullable()();
  DateTimeColumn get createdAt => dateTime()();
  DateTimeColumn get updatedAt => dateTime()();
}

@DriftDatabase(tables: [CachedPosts, CachedInboxItems, DraftPosts])
class AppDatabase extends _$AppDatabase {
  AppDatabase() : super(_openConnection());

  @override
  int get schemaVersion => 1;

  // Posts
  Future<List<CachedPost>> getCachedPosts() => select(cachedPosts).get();

  Future<void> cachePosts(List<CachedPostsCompanion> posts) async {
    await batch((batch) {
      batch.insertAllOnConflictUpdate(cachedPosts, posts);
    });
  }

  // Inbox
  Future<List<CachedInboxItem>> getCachedInbox() => select(cachedInboxItems).get();

  // Drafts
  Future<List<DraftPost>> getDrafts() => select(draftPosts).get();

  Future<int> saveDraft(DraftPostsCompanion draft) =>
      into(draftPosts).insert(draft);

  Future<void> updateDraft(DraftPost draft) => update(draftPosts).replace(draft);

  Future<void> deleteDraft(int id) =>
      (delete(draftPosts)..where((t) => t.id.equals(id))).go();
}

LazyDatabase _openConnection() {
  return LazyDatabase(() async {
    final dbFolder = await getApplicationDocumentsDirectory();
    final file = File(p.join(dbFolder.path, 'bizsocials.db'));
    return NativeDatabase(file);
  });
}
```

### 7.2 Sync Manager
```dart
// lib/core/sync/sync_manager.dart

class SyncManager {
  final AppDatabase _database;
  final ApiClient _apiClient;
  final ConnectivityService _connectivity;

  SyncManager({
    required AppDatabase database,
    required ApiClient apiClient,
    required ConnectivityService connectivity,
  })  : _database = database,
        _apiClient = apiClient,
        _connectivity = connectivity;

  Future<void> syncPendingChanges() async {
    if (!await _connectivity.isConnected) return;

    // Sync pending posts
    await _syncPendingPosts();

    // Sync pending replies
    await _syncPendingReplies();

    // Refresh cached data
    await _refreshCache();
  }

  Future<void> _syncPendingPosts() async {
    final pendingPosts = await _database.getPendingSyncPosts();

    for (final post in pendingPosts) {
      try {
        await _apiClient.post('/posts', data: post.toJson());

        // Mark as synced
        await _database.markPostSynced(post.id);
      } catch (e) {
        // Will retry on next sync
        continue;
      }
    }
  }

  Future<void> _refreshCache() async {
    // Fetch and cache latest posts
    final postsResponse = await _apiClient.get('/posts');
    final posts = (postsResponse.data['data'] as List)
        .map((e) => CachedPostsCompanion.fromJson(e))
        .toList();
    await _database.cachePosts(posts);

    // Fetch and cache inbox
    final inboxResponse = await _apiClient.get('/inbox');
    // ... cache inbox items
  }
}
```

---

## 8. Widget Support (Phase 3)

### 8.1 iOS Widgets
```swift
// ios/BizSocialsWidget/BizSocialsWidget.swift

import WidgetKit
import SwiftUI

struct ScheduledPostEntry: TimelineEntry {
    let date: Date
    let posts: [ScheduledPost]
}

struct ScheduledPost: Identifiable {
    let id: String
    let content: String
    let scheduledTime: Date
    let platform: String
}

struct Provider: TimelineProvider {
    func placeholder(in context: Context) -> ScheduledPostEntry {
        ScheduledPostEntry(date: Date(), posts: [])
    }

    func getSnapshot(in context: Context, completion: @escaping (ScheduledPostEntry) -> Void) {
        let entry = ScheduledPostEntry(date: Date(), posts: samplePosts())
        completion(entry)
    }

    func getTimeline(in context: Context, completion: @escaping (Timeline<ScheduledPostEntry>) -> Void) {
        // Fetch upcoming posts from shared UserDefaults (set by Flutter)
        let posts = fetchUpcomingPosts()
        let entry = ScheduledPostEntry(date: Date(), posts: posts)

        // Refresh every hour
        let nextUpdate = Calendar.current.date(byAdding: .hour, value: 1, to: Date())!
        let timeline = Timeline(entries: [entry], policy: .after(nextUpdate))
        completion(timeline)
    }

    private func fetchUpcomingPosts() -> [ScheduledPost] {
        guard let sharedDefaults = UserDefaults(suiteName: "group.com.bizsocials.app"),
              let data = sharedDefaults.data(forKey: "scheduled_posts"),
              let posts = try? JSONDecoder().decode([ScheduledPost].self, from: data)
        else {
            return []
        }
        return posts
    }
}

struct BizSocialsWidgetEntryView: View {
    var entry: Provider.Entry
    @Environment(\.widgetFamily) var family

    var body: some View {
        switch family {
        case .systemSmall:
            SmallWidgetView(posts: entry.posts)
        case .systemMedium:
            MediumWidgetView(posts: entry.posts)
        default:
            Text("BizSocials")
        }
    }
}

struct SmallWidgetView: View {
    let posts: [ScheduledPost]

    var body: some View {
        VStack(alignment: .leading) {
            Text("Next Post")
                .font(.caption)
                .foregroundColor(.secondary)

            if let nextPost = posts.first {
                Text(nextPost.content)
                    .font(.subheadline)
                    .lineLimit(2)

                Spacer()

                HStack {
                    Image(systemName: platformIcon(nextPost.platform))
                    Text(formatTime(nextPost.scheduledTime))
                        .font(.caption)
                }
            } else {
                Text("No scheduled posts")
                    .font(.subheadline)
            }
        }
        .padding()
    }
}

@main
struct BizSocialsWidget: Widget {
    let kind: String = "BizSocialsWidget"

    var body: some WidgetConfiguration {
        StaticConfiguration(kind: kind, provider: Provider()) { entry in
            BizSocialsWidgetEntryView(entry: entry)
        }
        .configurationDisplayName("Scheduled Posts")
        .description("View your upcoming scheduled posts.")
        .supportedFamilies([.systemSmall, .systemMedium])
    }
}
```

---

## 9. App Store & Play Store

### 9.1 App Store Metadata
```yaml
# App Store Connect metadata
app_name: BizSocials
subtitle: Social Media Management
category: Business
secondary_category: Productivity

keywords:
  - social media
  - scheduling
  - marketing
  - content
  - analytics
  - instagram
  - linkedin
  - twitter
  - facebook

description: |
  BizSocials is your all-in-one social media management solution.
  Schedule posts, manage your inbox, track analytics, and collaborate
  with your team - all from your mobile device.

  KEY FEATURES:

  - Schedule & Publish Posts
  Schedule posts across LinkedIn, Instagram, Facebook, Twitter, and more.
  Preview how your posts will look before publishing.

  - Social Inbox
  Manage comments, messages, and mentions in one unified inbox.
  Never miss an engagement opportunity.

  - AI-Powered Tools
  Generate captions, suggest hashtags, and find the best time to post
  with our AI assistant.

  - Analytics Dashboard
  Track your performance with detailed analytics. Understand what
  content resonates with your audience.

  - Team Collaboration
  Work together with approval workflows, comments, and task assignments.

  - Content Calendar
  Visualize your content strategy with our intuitive calendar view.

  Download BizSocials today and take control of your social media presence.

whats_new: |
  Version 1.0.0
  - Initial release
  - Schedule and publish posts
  - Social inbox management
  - AI caption generator
  - Analytics dashboard
  - Push notifications
```

### 9.2 Privacy Labels
```yaml
# App Privacy
data_types_collected:
  - type: Contact Info
    purpose: App Functionality
    linked_to_identity: true

  - type: User Content
    purpose: App Functionality
    linked_to_identity: true

  - type: Identifiers
    purpose: Analytics, App Functionality
    linked_to_identity: true

  - type: Usage Data
    purpose: Analytics
    linked_to_identity: false

data_not_collected:
  - Financial Info
  - Health & Fitness
  - Location
  - Sensitive Info

third_party_sharing: No
tracking: No
```

---

## 10. Dependencies

### 10.1 pubspec.yaml
```yaml
name: bizsocials_mobile
description: BizSocials Mobile App
version: 1.0.0+1

environment:
  sdk: '>=3.0.0 <4.0.0'
  flutter: '>=3.10.0'

dependencies:
  flutter:
    sdk: flutter

  # State Management
  flutter_bloc: ^8.1.3
  freezed_annotation: ^2.4.1

  # Networking
  dio: ^5.4.0
  retrofit: ^4.0.3

  # Local Storage
  drift: ^2.14.1
  sqlite3_flutter_libs: ^0.5.18
  shared_preferences: ^2.2.2
  flutter_secure_storage: ^9.0.0

  # Firebase
  firebase_core: ^2.24.2
  firebase_messaging: ^14.7.10
  firebase_analytics: ^10.7.4
  firebase_crashlytics: ^3.4.9

  # Authentication
  local_auth: ^2.1.8

  # Media
  image_picker: ^1.0.7
  cached_network_image: ^3.3.1
  photo_view: ^0.14.0
  video_player: ^2.8.2

  # UI
  flutter_svg: ^2.0.9
  shimmer: ^3.0.0
  lottie: ^3.0.0
  pull_to_refresh: ^2.0.0
  flutter_slidable: ^3.0.1

  # Navigation
  go_router: ^13.0.1

  # Dependency Injection
  get_it: ^7.6.4
  injectable: ^2.3.2

  # Utilities
  dartz: ^0.10.1
  equatable: ^2.0.5
  intl: ^0.18.1
  url_launcher: ^6.2.2
  share_plus: ^7.2.1
  connectivity_plus: ^5.0.2

dev_dependencies:
  flutter_test:
    sdk: flutter

  # Code Generation
  build_runner: ^2.4.7
  freezed: ^2.4.5
  json_serializable: ^6.7.1
  retrofit_generator: ^8.0.6
  injectable_generator: ^2.4.1
  drift_dev: ^2.14.1

  # Testing
  bloc_test: ^9.1.5
  mocktail: ^1.0.1

  # Linting
  flutter_lints: ^3.0.1

flutter:
  uses-material-design: true

  assets:
    - assets/images/
    - assets/icons/
    - assets/animations/

  fonts:
    - family: Inter
      fonts:
        - asset: assets/fonts/Inter-Regular.ttf
        - asset: assets/fonts/Inter-Medium.ttf
          weight: 500
        - asset: assets/fonts/Inter-SemiBold.ttf
          weight: 600
        - asset: assets/fonts/Inter-Bold.ttf
          weight: 700
```

---

## 11. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | 2025-02-06 | System | Initial specification |
