<?php

namespace App\Filament\Resources\AiArticleResource\Schemas;

use App\Models\WpSite;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AiArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('AI Generation Settings')
                    ->description('Optional settings to fine-tune your AI-generated content.')
                    ->icon('heroicon-o-cog')
                    ->columns(1)
                    ->columnSpanFull()
                    ->collapsed()
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('word_count_target')
                            ->label('Target Word Count')
                            ->placeholder('1000')
                            ->numeric()
                            ->default(1000)
                            ->helperText('Approximate word count for the article')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('content_type')
                            ->label('Content Type')
                            ->helperText('Choose the type of content to generate')
                            ->options([
                                'blog_post' => 'Blog Post',
                                'tutorial' => 'Tutorial',
                                'listicle' => 'Listicle',
                                'how_to' => 'How-To Guide',
                                'opinion' => 'Opinion Piece',
                                'news' => 'News Article',
                            ])
                            ->default('blog_post')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('additional_instructions')
                            ->label('Persona & Instructions')
                            ->placeholder('e.g., I am a professional plumber in Abu Dhabi with 10 years of experience at Permafix Plumbing. Write in a friendly but authoritative tone.')
                            ->rows(3)
                            ->helperText('Describe who should write this article (your persona) and any specific requirements')
                            ->columnSpanFull(),
                    ]),

                Section::make('Article Configuration')
                    ->description('Set up your AI article parameters and let our AI create compelling content for you.')
                    ->icon('heroicon-o-document')
                    ->columns(1)
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Select::make('wp_site_id')
                            ->label('Target WordPress Site')
                            ->helperText('Choose where this article will be published')
                            ->relationship('wpSite', 'site_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('topic', null))
                            ->placeholder('Select a WordPress site...')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('topic')
                            ->label('Article Topic')
                            ->placeholder('e.g., "10 Essential Laravel Tips for Developers"')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Enter a clear, engaging topic for your article')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!empty($state)) {
                                    $set('keywords', self::generateKeywordsFromTopic($state));
                                }
                            })
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('keywords')
                            ->label('Keywords')
                            ->placeholder('Laravel, PHP, web development, tutorial')
                            ->rows(3)
                            ->helperText('Comma-separated keywords for SEO optimization')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('tone')
                            ->label('Writing Tone')
                            ->helperText('Choose the writing style for your article')
                            ->options([
                                'professional' => 'Professional',
                                'casual' => 'Casual',
                                'friendly' => 'Friendly',
                                'technical' => 'Technical',
                                'conversational' => 'Conversational',
                                'formal' => 'Formal',
                            ])
                            ->default('professional')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Generated Content')
                    ->description('Your AI-generated content will appear here once processing is complete.')
                    ->icon('heroicon-o-star')
                    ->columns(1)
                    ->columnSpanFull()
                    ->collapsed()
                    ->collapsible()
                    ->poll('5s') // Check for updates every 5 seconds
                    ->schema([
                        Forms\Components\Placeholder::make('generation_status')
                            ->label('Generation Status')
                            ->content(function ($record) {
                                if (!$record) return 'Ready to generate';
                                
                                return match($record->status) {
                                    'pending' => 'Ready to generate',
                                    'generating' => 'AI is generating your content...',
                                    'generating_image' => 'AI is generating featured image...',
                                    'publishing' => 'Publishing to WordPress...',
                                    'ready' => 'Content generated successfully!',
                                    'published' => 'Published to WordPress',
                                    'failed' => '❌ Generation failed. Please check your OpenAI API quota and try again.',
                                    default => 'Ready to generate'
                                };
                            })
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('title')
                            ->label('Generated Title')
                            ->placeholder('Your AI-generated title will appear here...')
                            ->disabled()
                            ->maxLength(255)
                            ->default(fn ($record) => $record?->title)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('content')
                            ->label('Generated Content')
                            ->placeholder('Your AI-generated article content will appear here...')
                            ->disabled()
                            ->default(fn ($record) => $record?->content)
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'bullet-list',
                                'number-list',
                                'heading',
                            ]),

                        Forms\Components\Placeholder::make('featured_image_display')
                            ->label('Featured Image')
                            ->content(function ($record) {
                                if (!$record || !$record->featured_image_url) {
                                    return 'No image generated yet';
                                }
                                
                                return new \Illuminate\Support\HtmlString(
                                    '<img src="' . asset($record->featured_image_url) . '" alt="Featured Image" style="max-width: 300px; max-height: 200px; height: auto; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">'
                                );
                            })
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('reading_time')
                            ->label('Reading Time')
                            ->placeholder('Reading time will appear here...')
                            ->disabled()
                            ->default(fn ($record) => $record?->reading_time)
                            ->columnSpanFull(),
                    ]),
                    
                Section::make('SEO Metadata')
                    ->description('Search engine optimization metadata generated by AI')
                    ->icon('heroicon-o-magnifying-glass')
                    ->columns(1)
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->label('Meta Title')
                            ->placeholder('SEO-optimized title will appear here...')
                            ->disabled()
                            ->maxLength(60)
                            ->default(fn ($record) => $record?->meta_title)
                            ->helperText('Ideal length is 50-60 characters for search engines')
                            ->columnSpanFull(),
                            
                        Forms\Components\Textarea::make('meta_description')
                            ->label('Meta Description')
                            ->placeholder('SEO meta description will appear here...')
                            ->disabled()
                            ->maxLength(160)
                            ->rows(2)
                            ->default(fn ($record) => $record?->meta_description)
                            ->helperText('Ideal length is 150-160 characters for search results')
                            ->columnSpanFull(),
                    ]),
                    
            ]);
    }

    /**
     * Generate keywords from topic using simple extraction
     */
    private static function generateKeywordsFromTopic(string $topic): string
    {
        // Simple keyword extraction - in real implementation, this could use AI
        $words = str_replace(['"', "'", '.', ',', '!', '?'], '', $topic);
        $words = explode(' ', $words);
        
        // Filter out common words and keep important ones
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'top', 'best', 'essential'];
        $keywords = array_filter($words, function($word) use ($stopWords) {
            return !in_array(strtolower($word), $stopWords) && strlen($word) > 2;
        });
        
        return implode(', ', array_slice($keywords, 0, 5));
    }
}
