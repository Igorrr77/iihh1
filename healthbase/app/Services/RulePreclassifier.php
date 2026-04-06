<?php

declare(strict_types=1);

namespace App\Services;

class RulePreclassifier
{
    private array $map = [
        'diabet' => ['диабет','сахар','глюкоза','hba1c','инсулин','предиабет'],
        'pohudenie' => ['вес','ожирение','похудение','жир','метаболизм'],
        'davlenie' => ['давление','гипертония'],
        'sosudy' => ['холестерин','атеросклероз','сосуды','инфаркт','инсульт'],
        'pechen-pishevarenie' => ['печень','желчный','кишечник','жкт','гепатоз'],
        'sustavy' => ['сустав','артроз','колено','воспаление суставов'],
        'onkoprotekciya' => ['рак','онкология','опухоль'],
        'pitanie' => ['питание','продукты','еда','рацион'],
        'dobavki' => ['витамин','магний','b12','омега','добавка','минерал'],
        'stress-son-mozg' => ['стресс','сон','мозг','память','усталость'],
        'faq-video' => ['вопрос','ответы','разбор вопросов'],
        'istorii' => ['отзыв','история','результат','как я смог'],
    ];

    public function guess(string $title, string $description): array
    {
        $text = mb_strtolower($title);
        $scores = [];
        foreach ($this->map as $slug => $terms) {
            $scores[$slug] = 0;
            foreach ($terms as $term) {
                if (str_contains($text, $term)) {
                    $scores[$slug]++;
                }
            }
        }
        arsort($scores);
        return array_keys(array_filter($scores));
    }
}
