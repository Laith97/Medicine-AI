<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DoctorLandingPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'username',
        'template',
        'page_title',
        'page_description',
        'tagline',
        'hero_image',
        'about_text',
        'colors',
        'section_visibility',
        'is_published',
        'custom_domain',
        'subdomain_enabled',
        'seo_settings',
        'default_language',
        'translations',
    ];

    protected $casts = [
        'colors' => 'array',
        'section_visibility' => 'array',
        'seo_settings' => 'array',
        'translations' => 'array',
        'is_published' => 'boolean',
        'subdomain_enabled' => 'boolean',
    ];

    /**
     * Get the doctor that owns the landing page
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get default colors
     */
    public function getDefaultColors()
    {
        return [
            'primary' => '#3b82f6',
            'secondary' => '#64748b',
            'accent' => '#10b981',
            'background' => '#ffffff',
            'text' => '#1f2937',
            'button' => '#3b82f6',
            'button_text' => '#ffffff',
            'header_bg' => '#ffffff',
            'footer_bg' => '#f8fafc',
        ];
    }

    /**
     * Get default section visibility
     */
    public function getDefaultSectionVisibility()
    {
        return [
            'hero' => true,
            'about' => true,
            'appointments' => true,
            'reviews' => true,
            'contact' => true,
            'chat_widget' => true,
        ];
    }

    /**
     * Get colors with defaults
     */
    public function getColorsAttribute($value)
    {
        $colors = json_decode($value, true) ?: [];
        return array_merge($this->getDefaultColors(), $colors);
    }

    /**
     * Get section visibility with defaults
     */
    public function getSectionVisibilityAttribute($value)
    {
        $visibility = json_decode($value, true) ?: [];
        return array_merge($this->getDefaultSectionVisibility(), $visibility);
    }

    /**
     * Get the landing page URL
     */
    public function getUrlAttribute()
    {
        if ($this->custom_domain) {
            return 'https://' . $this->custom_domain;
        }

        if ($this->subdomain_enabled) {
            return 'https://' . $this->username . '.' . config('app.domain', 'medcuraai.com');
        }

        return route('doctor.landing', $this->username);
    }

    /**
     * Get SEO title
     */
    public function getSeoTitle()
    {
        return $this->page_title ?:
               ($this->doctor->user->name . ' - ' . $this->doctor->specialty->name ?? 'Doctor');
    }

    /**
     * Get SEO description
     */
    public function getSeoDescription()
    {
        return $this->page_description ?:
               ('Book an appointment with ' . $this->doctor->user->name . ', ' .
                ($this->doctor->specialty->name ?? 'Medical Professional') .
                ' in ' . ($this->doctor->city ?? 'your area'));
    }

    /**
     * Scope for published pages
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope for finding by username
     */
    public function scopeByUsername($query, $username)
    {
        return $query->where('username', $username);
    }

    /**
     * Scope for finding by custom domain
     */
    public function scopeByCustomDomain($query, $domain)
    {
        return $query->where('custom_domain', $domain);
    }

    /**
     * Get translated content for a specific language
     */
    public function getTranslatedContent($language = null)
    {
        $language = $language ?: $this->default_language ?: 'en';
        $translations = $this->translations ?: [];

        return [
            'page_title' => $translations[$language]['page_title'] ?? $this->page_title,
            'page_description' => $translations[$language]['page_description'] ?? $this->page_description,
            'tagline' => $translations[$language]['tagline'] ?? $this->tagline,
            'about_text' => $translations[$language]['about_text'] ?? $this->about_text,

            // Appointment form translations
            'appointment_title' => $translations[$language]['appointment_title'] ?? null,
            'appointment_subtitle' => $translations[$language]['appointment_subtitle'] ?? null,
            'form_name_label' => $translations[$language]['form_name_label'] ?? null,
            'form_email_label' => $translations[$language]['form_email_label'] ?? null,
            'form_phone_label' => $translations[$language]['form_phone_label'] ?? null,
            'form_date_label' => $translations[$language]['form_date_label'] ?? null,
            'form_time_label' => $translations[$language]['form_time_label'] ?? null,
            'form_message_label' => $translations[$language]['form_message_label'] ?? null,
            'form_submit_button' => $translations[$language]['form_submit_button'] ?? null,

            // Navigation translations
            'nav_home' => $translations[$language]['nav_home'] ?? null,
            'nav_about' => $translations[$language]['nav_about'] ?? null,
            'nav_appointments' => $translations[$language]['nav_appointments'] ?? null,
            'nav_reviews' => $translations[$language]['nav_reviews'] ?? null,
            'nav_contact' => $translations[$language]['nav_contact'] ?? null,
            'about_title' => $translations[$language]['about_title'] ?? null,
        ];
    }

    /**
     * Set translation for a specific language
     */
    public function setTranslation($language, $field, $value)
    {
        $translations = $this->translations ?: [];
        $translations[$language][$field] = $value;
        $this->translations = $translations;
        $this->save();
    }

    /**
     * Get available languages
     */
    public function getAvailableLanguages()
    {
        $translations = $this->translations ?: [];
        $languages = ['en']; // Default English

        foreach (array_keys($translations) as $lang) {
            if (!in_array($lang, $languages)) {
                $languages[] = $lang;
            }
        }

        return $languages;
    }
}
