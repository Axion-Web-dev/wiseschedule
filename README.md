# Social Engage - AI-Powered Social Media Automation Platform

A sophisticated Laravel-based platform that automates content creation, scheduling, and publishing across multiple WordPress sites and social media platforms using advanced AI technology.

## 🚀 Features

### 🤖 AI Content Generation
- **Smart Article Creation**: Generate high-quality blog posts, tutorials, and articles using OpenAI GPT models
- **Multiple Content Types**: Support for blog posts, tutorials, listicles, how-to guides, opinion pieces, and news articles
- **Tone Adjustment**: Choose from professional, casual, friendly, technical, conversational, or formal tones
- **SEO Optimization**: Automatic meta title and description generation for better search rankings
- **Content Refinement**: Advanced AI-powered content improvement and refinement

### 📅 Advanced Scheduling System
- **Timezone-Aware Scheduling**: Full timezone support with automatic detection and conversion
- **Facebook-Style DateTime Picker**: Intuitive scheduling interface with 5-minute intervals
- **UTC Storage**: All times stored in UTC for global consistency
- **WordPress Integration**: Proper `date` and `date_gmt` handling for WordPress sites
- **Queue-Based Publishing**: Reliable job queue system for scheduled content

### 🔗 Multi-Platform Integration
- **WordPress Publishing**: Direct publishing to unlimited WordPress sites
- **X (Twitter) Integration**: Automated thread posting with media support
- **Bulk Operations**: Process multiple articles simultaneously
- **Auto-Sync**: Automatic fetching of new posts from connected WordPress sites

### 🎨 Professional Admin Panel
- **Filament Admin**: Modern, responsive admin interface
- **Real-Time Dashboard**: Live status updates and analytics
- **Resource Management**: Comprehensive resource pages for articles, sites, and posts
- **Security Features**: Secure password handling, encrypted credentials, and user authentication

### 🌐 Enterprise Features
- **Multi-Tenant Architecture**: Support for multiple users and WordPress sites
- **Role-Based Access**: User permissions and access control
- **Audit Logging**: Comprehensive activity tracking
- **Error Handling**: Robust error management and retry mechanisms

## 📋 System Requirements

- **PHP**: ^8.2
- **Laravel**: ^12.0
- **Database**: MySQL 8.0+ or PostgreSQL 12+ or SQLite 3.8+
- **Node.js**: ^18.0
- **Composer**: ^2.0

## 🛠️ Installation

### 1. Clone the Repository
```bash
git clone https://github.com/your-username/social-engage.git
cd social-engage
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Environment Variables
Edit your `.env` file with the following required settings:

```env
# Application
APP_NAME="Social Engage"
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=social_engage
DB_USERNAME=your_username
DB_PASSWORD=your_password

# OpenAI API
OPENAI_API_KEY=your_openai_api_key_here

# Twitter/X API (Optional)
TWITTER_CLIENT_ID=your_twitter_client_id
TWITTER_CLIENT_SECRET=your_twitter_client_secret
TWITTER_REDIRECT_URI=http://localhost:8000/x/callback

# Hugging Face (Optional - for AI image generation)
HF_TOKEN=your_huggingface_token
```

### 5. Database Setup
```bash
php artisan migrate
php artisan db:seed
```

### 6. Build Assets
```bash
npm run build
```

### 7. Start the Application
```bash
php artisan serve
```

Visit `http://localhost:8000` to access your application.

## 🔧 Configuration

### OpenAI Integration
1. Get your API key from [OpenAI Platform](https://platform.openai.com/api-keys)
2. Add it to your `.env` file as `OPENAI_API_KEY`
3. The system uses GPT-4.1-nano model for optimal performance and cost

### Twitter/X Integration
1. Create a Twitter Developer account at [Twitter Developer Portal](https://developer.twitter.com/)
2. Create a new app with OAuth 2.0 credentials
3. Add your credentials to the `.env` file
4. Set the callback URL to: `http://localhost:8000/x/callback`

### WordPress Site Connection
1. Navigate to Admin → WordPress Sites
2. Click "Add New Site"
3. Enter your site URL and WordPress Application Password credentials
4. Test connection and save

### AI Image Generation (Optional)
1. Get a Hugging Face token from [Hugging Face](https://huggingface.co/settings/tokens)
2. Add `HF_TOKEN` to your `.env` file
3. Enable image generation in article settings

## 📖 Usage Guide

### Creating AI Articles
1. **Navigate**: Admin → AI Articles → Create Article
2. **Basic Settings**:
   - Topic: Enter your article topic
   - Keywords: Add relevant keywords (comma-separated)
   - Content Type: Choose from blog post, tutorial, etc.
   - Tone: Select the desired writing tone
   - Word Count: Set target word count (500-5000)
3. **Advanced Options**:
   - Additional Instructions: Provide specific requirements
   - Schedule Later: Enable to set publication date/time
4. **Generate**: Click "Generate Article" to create content
5. **Review & Publish**: Review generated content and publish to WordPress

### Managing WordPress Sites
1. **Add Site**: Admin → WordPress Sites → Create
2. **Site Details**:
   - Site Name: Display name for the site
   - Site URL: Full WordPress site URL
   - Credentials: WordPress username and Application Password
   - Timezone: Site's local timezone
3. **Test Connection**: Verify credentials before saving
4. **Auto-Sync**: Enable automatic post fetching

### Scheduling Content
1. **Enable Scheduling**: Toggle "Schedule Later" in article creation
2. **Set Date/Time**: Use the Facebook-style datetime picker
3. **Timezone Support**: Times automatically display in your local timezone
4. **Queue Processing**: Content publishes automatically at scheduled time

### Twitter/X Integration
1. **Connect Account**: Admin → Twitter → Connect Account
2. **Select Site**: Choose which WordPress site to associate
3. **Authorize**: Complete OAuth flow with Twitter
4. **Auto-Posting**: Articles automatically post as Twitter threads when published

## 🏗️ Architecture

### Core Components

#### Services
- **AiService**: Handles OpenAI API integration and content generation
- **WordPressService**: Manages WordPress API interactions
- **SocialMediaService**: Handles Twitter/X posting and media management

#### Jobs
- **GenerateAiArticle**: Queue-based AI content generation
- **RefineArticleContent**: Content improvement and refinement
- **PublishToWordPress**: Scheduled publishing to WordPress sites
- **GenerateGeminiImageJob**: AI image generation (optional)

#### Models
- **AiArticle**: AI-generated content management
- **WpSite**: WordPress site configuration and credentials
- **Post**: Synchronized WordPress posts
- **User**: User management with timezone support

### Timezone Handling
The system implements enterprise-level timezone management:

1. **UTC Storage**: All datetimes stored in UTC in database
2. **User Detection**: Automatic timezone detection via browser
3. **Display Conversion**: Times shown in user's local timezone
4. **WordPress Compatibility**: Proper `date`/`date_gmt` field handling
5. **Job Scheduling**: Queue jobs use UTC for global consistency

## 🔄 API Endpoints

### WordPress Integration
- `GET /wp-json/wp/v2/posts` - Fetch posts
- `POST /wp-json/wp/v2/posts` - Create posts
- `POST /wp-json/wp/v2/media` - Upload media

### Twitter/X Integration
- `GET /x/redirect` - OAuth redirect
- `GET /x/callback` - OAuth callback
- `GET /x/disconnect` - Disconnect account

### Internal APIs
- `POST /timezone-detect` - User timezone detection
- `POST /start-trial` - Trial registration redirect

## 🧪 Testing

### Run Tests
```bash
php artisan test
```

### Test Coverage
- Unit tests for core services
- Feature tests for API endpoints
- Integration tests for WordPress publishing
- Job queue testing

## 📊 Monitoring & Logging

### Logging Channels
- **Default**: Application logs
- **Queue**: Job processing logs
- **WordPress**: API interaction logs
- **Twitter**: Social media integration logs

### Error Handling
- Comprehensive exception handling
- Automatic retry mechanisms for failed jobs
- User-friendly error messages
- Admin notifications for critical errors

## 🚀 Deployment

### Production Setup
1. **Environment**: Set `APP_ENV=production` and `APP_DEBUG=false`
2. **Optimization**: Run `php artisan optimize` and `php artisan config:cache`
3. **Scheduler**: Set up Laravel Task Scheduler
4. **Queue Worker**: Configure supervisor for queue processing
5. **SSL**: Enable HTTPS for all API integrations

### Queue Configuration
```bash
# Start queue worker
php artisan queue:work

# Install supervisor for production
sudo apt-get install supervisor
```

### Task Scheduler
Add to your crontab:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## 🔒 Security

### Implemented Features
- **Encrypted Credentials**: All third-party credentials encrypted
- **Secure Authentication**: Laravel's built-in authentication system
- **CSRF Protection**: Cross-site request forgery protection
- **Input Validation**: Comprehensive input sanitization
- **Rate Limiting**: API rate limiting for external services

### Best Practices
- Regular security updates
- Environment variable protection
- Database encryption for sensitive data
- HTTPS enforcement in production

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

### Documentation
- [Laravel Documentation](https://laravel.com/docs)
- [Filament Documentation](https://filamentphp.com/docs)
- [OpenAI API Documentation](https://platform.openai.com/docs)

### Common Issues
- **OpenAI Rate Limits**: Check your API quota and usage
- **WordPress Connection**: Verify Application Password permissions
- **Twitter OAuth**: Ensure callback URL matches developer settings
- **Queue Processing**: Check queue worker status and configuration

### Getting Help
- Create an issue in the GitHub repository
- Check existing issues for solutions
- Review logs for detailed error information

## 🎯 Roadmap

### Upcoming Features
- [ ] LinkedIn integration
- [ ] Facebook page publishing
- [ ] Advanced analytics dashboard
- [ ] Content calendar view
- [ ] Team collaboration features
- [ ] API rate limiting dashboard
- [ ] Content template library
- [ ] A/B testing for content

### Performance Improvements
- [ ] Redis caching implementation
- [ ] Database query optimization
- [ ] CDN integration for media files
- [ ] Background processing optimization

---

**Built with ❤️ using Laravel, Filament, and modern web technologies.**
