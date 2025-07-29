<?php

namespace App\Services;

use App\Models\Doctor;

class ChatService
{
    /**
     * Detect the language of a message
     */
    public function detectLanguage($message)
    {
        // Simple language detection based on common words and patterns
        $message = strtolower(trim($message));

        // Arabic detection
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $message)) {
            return 'ar';
        }

        // Spanish detection
        $spanishWords = ['hola', 'gracias', 'por favor', 'buenos días', 'buenas tardes', 'buenas noches', '¿', '¡'];
        foreach ($spanishWords as $word) {
            if (str_contains($message, $word)) {
                return 'es';
            }
        }

        // French detection
        $frenchWords = ['bonjour', 'merci', 's\'il vous plaît', 'comment', 'français', 'où', 'pourquoi'];
        foreach ($frenchWords as $word) {
            if (str_contains($message, $word)) {
                return 'fr';
            }
        }

        // German detection
        $germanWords = ['hallo', 'danke', 'bitte', 'guten tag', 'guten morgen', 'wie', 'wo', 'warum'];
        foreach ($germanWords as $word) {
            if (str_contains($message, $word)) {
                return 'de';
            }
        }

        // Italian detection
        $italianWords = ['ciao', 'grazie', 'prego', 'buongiorno', 'buonasera', 'come', 'dove', 'perché'];
        foreach ($italianWords as $word) {
            if (str_contains($message, $word)) {
                return 'it';
            }
        }

        // Portuguese detection
        $portugueseWords = ['olá', 'obrigado', 'obrigada', 'por favor', 'bom dia', 'boa tarde', 'boa noite'];
        foreach ($portugueseWords as $word) {
            if (str_contains($message, $word)) {
                return 'pt';
            }
        }

        // Default to English
        return 'en';
    }

    /**
     * Generate welcome message in appropriate language
     */
    public function generateWelcomeMessage($doctor, $language = 'en')
    {
        $doctorName = $doctor->user->name;

        // Check for custom welcome message
        if ($doctor->ai_chat_settings && !empty($doctor->ai_chat_settings['welcome_message'])) {
            return $doctor->ai_chat_settings['welcome_message'];
        }

        $messages = [
            'en' => [
                "Hello! I'm Dr. {$doctorName}'s AI assistant. How can I help you today?",
                "Welcome! I'm here to help answer your questions about Dr. {$doctorName}'s services. What would you like to know?",
                "Hi there! I'm Dr. {$doctorName}'s virtual assistant. Feel free to ask me about appointments, services, or any health-related questions.",
            ],
            'ar' => [
                "مرحباً! أنا المساعد الذكي للدكتور {$doctorName}. كيف يمكنني مساعدتك اليوم؟",
                "أهلاً وسهلاً! أنا هنا لمساعدتك في الإجابة على أسئلتك حول خدمات الدكتور {$doctorName}. ماذا تريد أن تعرف؟",
                "مرحباً بك! أنا المساعد الافتراضي للدكتور {$doctorName}. لا تتردد في سؤالي عن المواعيد أو الخدمات أو أي أسئلة متعلقة بالصحة.",
            ],
            'es' => [
                "¡Hola! Soy el asistente de IA del Dr. {$doctorName}. ¿Cómo puedo ayudarte hoy?",
                "¡Bienvenido! Estoy aquí para ayudarte a responder tus preguntas sobre los servicios del Dr. {$doctorName}. ¿Qué te gustaría saber?",
                "¡Hola! Soy el asistente virtual del Dr. {$doctorName}. No dudes en preguntarme sobre citas, servicios o cualquier pregunta relacionada con la salud.",
            ],
            'fr' => [
                "Bonjour! Je suis l'assistant IA du Dr. {$doctorName}. Comment puis-je vous aider aujourd'hui?",
                "Bienvenue! Je suis là pour vous aider à répondre à vos questions sur les services du Dr. {$doctorName}. Que souhaitez-vous savoir?",
                "Salut! Je suis l'assistant virtuel du Dr. {$doctorName}. N'hésitez pas à me poser des questions sur les rendez-vous, les services ou toute question liée à la santé.",
            ]
        ];

        $languageMessages = $messages[$language] ?? $messages['en'];
        return $languageMessages[array_rand($languageMessages)];
    }

    /**
     * Generate bot response in appropriate language
     */
    public function generateBotResponse($message, $doctor, $language = 'en')
    {
        if (!$doctor->ai_chat_enabled) {
            return $this->generateManualOnlyMessage($doctor, $language);
        }

        $message = strtolower($message);
        $doctorName = $doctor->user->name;

        // Check for appointments/booking
        if ($this->containsKeywords($message, ['appointment', 'book', 'schedule'], $language)) {
            return $this->getAppointmentMessage($doctor, $language);
        }

        // Check for price/cost
        if ($this->containsKeywords($message, ['price', 'cost', 'fee'], $language)) {
            return $this->getPriceMessage($doctor, $language);
        }

        // Check for location/address
        if ($this->containsKeywords($message, ['location', 'address', 'where'], $language)) {
            return $this->getLocationMessage($doctor, $language);
        }

        // Check for hours/time
        if ($this->containsKeywords($message, ['hours', 'time', 'open'], $language)) {
            return $this->getHoursMessage($doctor, $language);
        }

        // Check for insurance
        if ($this->containsKeywords($message, ['insurance', 'coverage'], $language)) {
            return $this->getInsuranceMessage($doctor, $language);
        }

        // Check for emergency
        if ($this->containsKeywords($message, ['emergency', 'urgent'], $language)) {
            return $this->getEmergencyMessage($doctor, $language);
        }

        // Check for specialty
        if ($this->containsKeywords($message, ['specialty', 'specializes'], $language)) {
            return $this->getSpecialtyMessage($doctor, $language);
        }

        // Default responses
        return $this->getDefaultMessage($doctor, $language);
    }

    /**
     * Check if message contains keywords in any language
     */
    private function containsKeywords($message, $englishKeywords, $language)
    {
        $keywords = [
            'en' => $englishKeywords,
            'ar' => $this->getArabicKeywords($englishKeywords),
            'es' => $this->getSpanishKeywords($englishKeywords),
            'fr' => $this->getFrenchKeywords($englishKeywords),
        ];

        $keywordsToCheck = array_merge(
            $keywords['en'],
            $keywords[$language] ?? []
        );

        foreach ($keywordsToCheck as $keyword) {
            if (str_contains($message, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get Arabic keywords
     */
    private function getArabicKeywords($englishKeywords)
    {
        $translations = [
            'appointment' => 'موعد',
            'book' => 'حجز',
            'schedule' => 'جدولة',
            'price' => 'سعر',
            'cost' => 'تكلفة',
            'fee' => 'رسوم',
            'location' => 'موقع',
            'address' => 'عنوان',
            'where' => 'أين',
            'hours' => 'ساعات',
            'time' => 'وقت',
            'open' => 'مفتوح',
            'insurance' => 'تأمين',
            'coverage' => 'تغطية',
            'emergency' => 'طوارئ',
            'urgent' => 'عاجل',
            'specialty' => 'تخصص',
            'specializes' => 'متخصص',
        ];

        return array_intersect_key($translations, array_flip($englishKeywords));
    }

    /**
     * Get Spanish keywords
     */
    private function getSpanishKeywords($englishKeywords)
    {
        $translations = [
            'appointment' => 'cita',
            'book' => 'reservar',
            'schedule' => 'programar',
            'price' => 'precio',
            'cost' => 'costo',
            'fee' => 'tarifa',
            'location' => 'ubicación',
            'address' => 'dirección',
            'where' => 'dónde',
            'hours' => 'horas',
            'time' => 'tiempo',
            'open' => 'abierto',
            'insurance' => 'seguro',
            'coverage' => 'cobertura',
            'emergency' => 'emergencia',
            'urgent' => 'urgente',
            'specialty' => 'especialidad',
            'specializes' => 'especializa',
        ];

        return array_intersect_key($translations, array_flip($englishKeywords));
    }

    /**
     * Get French keywords
     */
    private function getFrenchKeywords($englishKeywords)
    {
        $translations = [
            'appointment' => 'rendez-vous',
            'book' => 'réserver',
            'schedule' => 'programmer',
            'price' => 'prix',
            'cost' => 'coût',
            'fee' => 'frais',
            'location' => 'emplacement',
            'address' => 'adresse',
            'where' => 'où',
            'hours' => 'heures',
            'time' => 'temps',
            'open' => 'ouvert',
            'insurance' => 'assurance',
            'coverage' => 'couverture',
            'emergency' => 'urgence',
            'urgent' => 'urgent',
            'specialty' => 'spécialité',
            'specializes' => 'spécialise',
        ];

        return array_intersect_key($translations, array_flip($englishKeywords));
    }

    /**
     * Generate message when AI is disabled
     */
    private function generateManualOnlyMessage($doctor, $language)
    {
        $messages = [
            'en' => "Thank you for your message! Dr. {$doctor->user->name} or a member of our team will respond to you shortly. For urgent matters, please contact our office directly.",
            'ar' => "شكراً لك على رسالتك! سيقوم الدكتور {$doctor->user->name} أو أحد أعضاء فريقنا بالرد عليك قريباً. للأمور العاجلة، يرجى الاتصال بعيادتنا مباشرة.",
            'es' => "¡Gracias por tu mensaje! El Dr. {$doctor->user->name} o un miembro de nuestro equipo te responderá en breve. Para asuntos urgentes, por favor contacta nuestra oficina directamente.",
            'fr' => "Merci pour votre message! Le Dr. {$doctor->user->name} ou un membre de notre équipe vous répondra sous peu. Pour les questions urgentes, veuillez contacter notre bureau directement."
        ];

        return $messages[$language] ?? $messages['en'];
    }

    /**
     * Get appointment message
     */
    private function getAppointmentMessage($doctor, $language)
    {
        $messages = [
            'en' => "I'd be happy to help you book an appointment with Dr. {$doctor->user->name}. You can schedule directly through this page or call our office. What type of consultation are you looking for?",
            'ar' => "سأكون سعيداً لمساعدتك في حجز موعد مع الدكتور {$doctor->user->name}. يمكنك الجدولة مباشرة من خلال هذه الصفحة أو الاتصال بعيادتنا. ما نوع الاستشارة التي تبحث عنها؟",
            'es' => "Me complace ayudarte a reservar una cita con el Dr. {$doctor->user->name}. Puedes programar directamente a través de esta página o llamar a nuestra oficina. ¿Qué tipo de consulta estás buscando?",
            'fr' => "Je serais ravi de vous aider à prendre rendez-vous avec le Dr. {$doctor->user->name}. Vous pouvez programmer directement via cette page ou appeler notre bureau. Quel type de consultation recherchez-vous?"
        ];

        return $messages[$language] ?? $messages['en'];
    }

    /**
     * Get price message
     */
    private function getPriceMessage($doctor, $language)
    {
        $fee = $doctor->consultation_fee_dollars ? "$" . $doctor->consultation_fee_dollars : "varies";

        $messages = [
            'en' => "Dr. {$doctor->user->name}'s consultation fee is {$fee}. This may vary depending on the type of consultation. Would you like to know more about our services?",
            'ar' => "رسوم استشارة الدكتور {$doctor->user->name} هي {$fee}. قد تختلف هذه الرسوم حسب نوع الاستشارة. هل تريد معرفة المزيد عن خدماتنا؟",
            'es' => "La tarifa de consulta del Dr. {$doctor->user->name} es {$fee}. Esto puede variar según el tipo de consulta. ¿Te gustaría saber más sobre nuestros servicios?",
            'fr' => "Les frais de consultation du Dr. {$doctor->user->name} sont de {$fee}. Cela peut varier selon le type de consultation. Souhaitez-vous en savoir plus sur nos services?"
        ];

        return $messages[$language] ?? $messages['en'];
    }

    /**
     * Get location message
     */
    private function getLocationMessage($doctor, $language)
    {
        $location = $doctor->city ? "in {$doctor->city}" : "at our clinic";

        $messages = [
            'en' => "Dr. {$doctor->user->name} practices {$location}. For the exact address and directions, please check the contact information on this page.",
            'ar' => "يمارس الدكتور {$doctor->user->name} {$location}. للعنوان الدقيق والاتجاهات، يرجى مراجعة معلومات الاتصال في هذه الصفحة.",
            'es' => "El Dr. {$doctor->user->name} practica {$location}. Para la dirección exacta e indicaciones, por favor revisa la información de contacto en esta página.",
            'fr' => "Le Dr. {$doctor->user->name} exerce {$location}. Pour l'adresse exacte et les directions, veuillez consulter les informations de contact sur cette page."
        ];

        return $messages[$language] ?? $messages['en'];
    }

    /**
     * Get hours message
     */
    private function getHoursMessage($doctor, $language)
    {
        $messages = [
            'en' => "Our office hours vary by day. You can see available appointment slots on this page, or contact us directly for more information about our schedule.",
            'ar' => "ساعات عمل عيادتنا تختلف حسب اليوم. يمكنك رؤية مواعيد الحجز المتاحة في هذه الصفحة، أو الاتصال بنا مباشرة لمزيد من المعلومات حول جدولنا.",
            'es' => "Nuestros horarios de oficina varían según el día. Puedes ver los horarios de citas disponibles en esta página, o contactarnos directamente para más información sobre nuestro horario.",
            'fr' => "Nos heures de bureau varient selon le jour. Vous pouvez voir les créneaux de rendez-vous disponibles sur cette page, ou nous contacter directement pour plus d'informations sur notre horaire."
        ];

        return $messages[$language] ?? $messages['en'];
    }

    /**
     * Get insurance message
     */
    private function getInsuranceMessage($doctor, $language)
    {
        $messages = [
            'en' => "For insurance coverage and payment options, please contact our office directly. We'll be happy to verify your benefits and discuss payment plans if needed.",
            'ar' => "للتغطية التأمينية وخيارات الدفع، يرجى الاتصال بعيادتنا مباشرة. سنكون سعداء للتحقق من مزاياك ومناقشة خطط الدفع إذا لزم الأمر.",
            'es' => "Para cobertura de seguro y opciones de pago, por favor contacta nuestra oficina directamente. Estaremos encantados de verificar tus beneficios y discutir planes de pago si es necesario.",
            'fr' => "Pour la couverture d'assurance et les options de paiement, veuillez contacter notre bureau directement. Nous serons heureux de vérifier vos avantages et de discuter des plans de paiement si nécessaire."
        ];

        return $messages[$language] ?? $messages['en'];
    }

    /**
     * Get emergency message
     */
    private function getEmergencyMessage($doctor, $language)
    {
        $messages = [
            'en' => "For medical emergencies, please call 911 or go to your nearest emergency room immediately. For urgent but non-emergency concerns, please contact our office directly.",
            'ar' => "للطوارئ الطبية، يرجى الاتصال بـ 911 أو الذهاب إلى أقرب غرفة طوارئ فوراً. للمخاوف العاجلة ولكن غير الطارئة، يرجى الاتصال بعيادتنا مباشرة.",
            'es' => "Para emergencias médicas, por favor llama al 911 o ve a tu sala de emergencias más cercana inmediatamente. Para preocupaciones urgentes pero no de emergencia, por favor contacta nuestra oficina directamente.",
            'fr' => "Pour les urgences médicales, veuillez appeler le 911 ou vous rendre immédiatement à la salle d'urgence la plus proche. Pour les préoccupations urgentes mais non urgentes, veuillez contacter notre bureau directement."
        ];

        return $messages[$language] ?? $messages['en'];
    }

    /**
     * Get specialty message
     */
    private function getSpecialtyMessage($doctor, $language)
    {
        $specialty = $doctor->specialty ? $doctor->specialty->name : "general practice";

        $messages = [
            'en' => "Dr. {$doctor->user->name} specializes in {$specialty}. Would you like to know more about the specific services we offer?",
            'ar' => "يتخصص الدكتور {$doctor->user->name} في {$specialty}. هل تريد معرفة المزيد عن الخدمات المحددة التي نقدمها؟",
            'es' => "El Dr. {$doctor->user->name} se especializa en {$specialty}. ¿Te gustaría saber más sobre los servicios específicos que ofrecemos?",
            'fr' => "Le Dr. {$doctor->user->name} se spécialise en {$specialty}. Souhaitez-vous en savoir plus sur les services spécifiques que nous offrons?"
        ];

        return $messages[$language] ?? $messages['en'];
    }

    /**
     * Get default message
     */
    private function getDefaultMessage($doctor, $language)
    {
        $messages = [
            'en' => [
                "Thank you for your question! For specific medical advice or detailed information, I recommend scheduling a consultation with Dr. {$doctor->user->name}. Is there anything else I can help you with?",
                "That's a great question! Dr. {$doctor->user->name} would be the best person to provide you with detailed information about that. Would you like to book an appointment?",
                "I understand your concern. For personalized medical advice, please consider scheduling a consultation with Dr. {$doctor->user->name}. Can I help you with anything else?",
            ],
            'ar' => [
                "شكراً لك على سؤالك! للحصول على نصائح طبية محددة أو معلومات تفصيلية، أنصح بجدولة استشارة مع الدكتور {$doctor->user->name}. هل يمكنني مساعدتك بأي شيء آخر؟",
                "هذا سؤال رائع! الدكتور {$doctor->user->name} هو أفضل شخص لتزويدك بمعلومات تفصيلية حول ذلك. هل تريد حجز موعد؟",
                "أفهم قلقك. للحصول على نصائح طبية شخصية، يرجى النظر في جدولة استشارة مع الدكتور {$doctor->user->name}. هل يمكنني مساعدتك بأي شيء آخر؟",
            ],
            'es' => [
                "¡Gracias por tu pregunta! Para consejos médicos específicos o información detallada, recomiendo programar una consulta con el Dr. {$doctor->user->name}. ¿Hay algo más en lo que pueda ayudarte?",
                "¡Esa es una gran pregunta! El Dr. {$doctor->user->name} sería la mejor persona para proporcionarte información detallada sobre eso. ¿Te gustaría reservar una cita?",
                "Entiendo tu preocupación. Para consejos médicos personalizados, por favor considera programar una consulta con el Dr. {$doctor->user->name}. ¿Puedo ayudarte con algo más?",
            ],
            'fr' => [
                "Merci pour votre question! Pour des conseils médicaux spécifiques ou des informations détaillées, je recommande de programmer une consultation avec le Dr. {$doctor->user->name}. Y a-t-il autre chose avec quoi je peux vous aider?",
                "C'est une excellente question! Le Dr. {$doctor->user->name} serait la meilleure personne pour vous fournir des informations détaillées à ce sujet. Souhaitez-vous prendre rendez-vous?",
                "Je comprends votre préoccupation. Pour des conseils médicaux personnalisés, veuillez envisager de programmer une consultation avec le Dr. {$doctor->user->name}. Puis-je vous aider avec autre chose?",
            ]
        ];

        $languageMessages = $messages[$language] ?? $messages['en'];
        return $languageMessages[array_rand($languageMessages)];
    }
}
