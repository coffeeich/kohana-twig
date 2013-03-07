<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Twig_Extensions extends Twig_Extension {

    public function getFunctions() {
        return array(

        );

    }

    public function getFilters() {
        return array(
            'number_format' => new Twig_Filter_Function('number_format'),
            'is_array'      => new Twig_Filter_Function('is_array'),
            'declination'   => new Twig_Filter_Method($this, 'declination'),
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName() {
        return 'kohana_twig_extensions';
    }

    /**
     * Склонялка
     *
     * @param int|numeric_string  $number         число для анализа
     * @param array|string        $wordsArray...  массив слов (напрмер для существительных
     *                                            в порядке: "год", "лет", "года",
     *                                            для прилагательных: "долгий", "долгих"),
     *                                            в качестве альтернативы вместо массива
     *                                            можно передать список аргументов:
     *                                            существительные:  declination($number, $word1, $word2, $word3)
     *                                            прилагательные:   declination($number, $word1, $word2)
     * @return string вернет строку в нужном склонении на основании переданного числа
     */
    public function declination($number, $wordsArray) {
        if (!is_array($wordsArray)) {
            $wordsArray = array_slice(func_get_args(), 1);
        }

        reset($wordsArray);

        if (!is_numeric($number)) {
            return "";
        }

        if (1 === count($wordsArray)) {
            return current($wordsArray);
        }

        $low = intval(substr("#{$number}", -1));

        if (1 === $low) {
            return current($wordsArray); // "год"
        }

        next($wordsArray);

        $high = intval(substr("#{$number}", -2));
        if ($high === 10 + $low) {
            return current($wordsArray); // "лет"
        }

        next($wordsArray);

        if (1 < $low && $low < 5 && count($wordsArray) === 3) {
            return current($wordsArray); // "года"
        }

        reset($wordsArray);

        return next($wordsArray); // "лет"
    }

}
