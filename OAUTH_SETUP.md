# OAuth2 Integration Setup Guide

This guide explains how to configure OAuth2 authentication for Google, GitHub, and LinkedIn in your Symfony application.

## Overview

The application now supports OAuth2 authentication using:
- **Google OAuth2** - For users with Google accounts
- **GitHub OAuth2** - For users with GitHub accounts  
- **LinkedIn OAuth2** - For users with LinkedIn accounts

## Implementation Details

This OAuth integration uses:
- **KnpUOAuth2ClientBundle** for OAuth client management
- **Direct controller authentication** (no firewall OAuth configuration)
- **Manual user creation/linking** in the database
- **Session-based authentication** compatible with existing Symfony security

## Prerequisites

You need to create OAuth applications on each provider's developer platform.

## 1. Google OAuth2 Setup

### 1.1 Create Google OAuth Application

1. Go to [Google Cloud Console](https://console.developers.google.com/)
2. Create a new project or select existing one
3. Go to **APIs & Services** → **Credentials**
4. Click **Create Credentials** → **OAuth client ID**
5. Select **Web application**
6. Configure:
   - **Name**: Your application name
   - **Authorized redirect URIs**: 
     ```
     http://localhost:8000/connect/google/check
     ```
   - For production: `https://yourdomain.com/connect/google/check`

### 1.2 Get Credentials

After creating the app, you'll receive:
- **Client ID** 
- **Client Secret**

### 1.3 Update Environment

Add to your `.env` file:
```bash
GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
```

## 2. GitHub OAuth2 Setup

### 2.1 Create GitHub OAuth Application

1. Go to [GitHub Developer Settings](https://github.com/settings/developers)
2. Click **New OAuth App**
3. Configure:
   - **Application name**: Your application name
   - **Homepage URL**: `http://localhost:8000` (or your production URL)
   - **Authorization callback URL**: 
     ```
     http://localhost:8000/connect/github/check
     ```
   - For production: `https://yourdomain.com/connect/github/check`

### 2.2 Get Credentials

After creating the app, you'll receive:
- **Client ID**
- **Client Secret**

### 2.3 Update Environment

Add to your `.env` file:
```bash
GITHUB_CLIENT_ID=your_github_client_id_here
GITHUB_CLIENT_SECRET=your_github_client_secret_here
```

## 3. LinkedIn OAuth2 Setup

### 3.1 Create LinkedIn OAuth Application

1. Go to [LinkedIn Developer Portal](https://www.linkedin.com/developers/apps)
2. Click **Create App**
3. Configure:
   - **App name**: Your application name
   - **LinkedIn Page**: Select or create a LinkedIn page
   - **App logo**: Upload your app logo
   - **Website URL**: `http://localhost:8000` (or your production URL)
   - **Redirect URL**: 
     ```
     http://localhost:8000/connect/linkedin/check
     ```
   - For production: `https://yourdomain.com/connect/linkedin/check`

### 3.2 Configure Products

In your LinkedIn app settings:
1. Go to **Products** tab
2. Add **Sign In with LinkedIn** product
3. Configure permissions:
   - `r_liteprofile` - Basic profile
   - `r_emailaddress` - Email address

### 3.3 Get Credentials

After creating the app, you'll receive:
- **Client ID**
- **Client Secret**

### 3.4 Update Environment

Add to your `.env` file:
```bash
LINKEDIN_CLIENT_ID=your_linkedin_client_id_here
LINKEDIN_CLIENT_SECRET=your_linkedin_client_secret_here
```

## 4. Database Setup

The OAuth integration adds the following fields to the User entity:
- `googleId` - Google user ID
- `githubId` - GitHub user ID  
- `linkedinId` - LinkedIn user ID
- `oauthProvider` - OAuth provider name
- `avatarUrl` - Profile picture URL

Run the database schema update:
```bash
php bin/console doctrine:schema:update --force
```

## 5. Testing

1. Clear the cache:
```bash
php bin/console cache:clear
```

2. Verify OAuth routes are registered:
```bash
php bin/console debug:router | findstr oauth
```

3. Visit the login page: `http://localhost:8000/login`

4. Click on any OAuth button to test the authentication flow

## 6. Security Considerations

- **Environment Variables**: Never commit OAuth credentials to version control
- **HTTPS**: Always use HTTPS in production for OAuth callbacks
- **Redirect URIs**: Ensure redirect URIs exactly match your application URLs
- **Scope Limitations**: Request only necessary permissions from users

## 7. Troubleshooting

### Common Issues

1. **"Invalid redirect URI" error**
   - Check that redirect URI in OAuth app settings matches exactly
   - Ensure no trailing slashes or protocol differences

2. **"Unable to get email" error**
   - For GitHub: Ensure user has public email or request email scope
   - For LinkedIn: Verify email permission is granted

3. **"The metadata storage is not up to date" error**
   - Run: `php bin/console doctrine:migrations:sync-metadata-storage`

4. **Bundle not found error**
   - Ensure KnpUOAuth2ClientBundle is registered in `config/bundles.php`

5. **"Unrecognized option oauth" error**
   - This implementation uses direct controller authentication, not firewall configuration
   - No OAuth configuration should be in `security.yaml`

### Debug Mode

To enable OAuth debugging, add to your `.env`:
```bash
APP_ENV=dev
```

## 8. Production Deployment

For production deployment:

1. Update all redirect URIs to use HTTPS
2. Set proper environment variables
3. Clear cache: `php bin/console cache:clear --env=prod`
4. Test OAuth flows in production environment

## 9. Customization

### Custom Redirect After OAuth

Modify the `authenticateUser()` method in `OAuthController` to customize post-login redirects.

### Additional OAuth Providers

To add more providers:
1. Install the appropriate OAuth client package
2. Configure in `knpu_oauth2_client.yaml`
3. Add routes in `OAuthController`
4. Add buttons to login template

### User Profile Completion

OAuth users get default values for required fields:
- `telephone`: "0000000000"
- `dateNaissance`: "1990-01-01"
- `roleUtilisateur`: "apprenant"

You may want to redirect them to a profile completion page after first OAuth login.

## 10. Architecture Notes

### Authentication Flow

1. User clicks OAuth button → Redirects to provider
2. Provider redirects back to `/connect/{provider}/check`
3. Controller fetches user data from provider
4. Controller finds/creates user in database
5. Controller manually authenticates user in Symfony session
6. User is redirected to home page

### Security Implementation

This approach uses:
- **No firewall OAuth configuration** (avoids Symfony 6+ compatibility issues)
- **Direct session authentication** compatible with existing security
- **Manual token creation** using `UsernamePasswordToken`
- **Provider-specific user identification** to prevent duplicate accounts

### Database Schema

```sql
ALTER TABLE user 
ADD google_id VARCHAR(100) NULL,
ADD github_id VARCHAR(100) NULL, 
ADD linkedin_id VARCHAR(100) NULL,
ADD oauth_provider VARCHAR(20) NULL,
ADD avatar_url VARCHAR(255) NULL;
```
