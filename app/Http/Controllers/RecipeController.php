<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class RecipeController extends Controller
{
    public function searchRecipes(Request $request)
    {
        // Kullanıcıdan gelen sorgu veya varsayılan değer
        $query = $request->input('query', 'pasta');

        // Guzzle HTTP Client'ı oluşturun
        $client = new Client();

        // .env'den API anahtarını alın
        $apiKey = env('SPOONACULAR_API_KEY');

        // Spoonacular API URL'si
        $url = 'https://api.spoonacular.com/recipes/complexSearch';

        try {
            // GET isteği gönderin
            $response = $client->request('GET', $url, [
                'query' => [
                    'apiKey' => $apiKey,
                    'query'  => $query,
                ],
                'verify' => false, // Sertifika doğrulamasını devre dışı bırakır
            ]);


            // Gelen cevabı JSON olarak decode edin
            $data = json_decode($response->getBody(), true);

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'API çağrısı sırasında hata oluştu.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function listRecipes(Request $request)
    {
        // Kullanıcıdan gelen sorgu parametresi, varsayılan değer boş bırakılabilir.
        $query = $request->input('query', ''); // Örneğin: salad, pasta vb.

        // Guzzle HTTP Client'ı oluşturun.
        $client = new Client();

        // .env dosyasından API anahtarını alın.
        $apiKey = env('SPOONACULAR_API_KEY');

        // Spoonacular API URL'si
        $url = 'https://api.spoonacular.com/recipes/complexSearch';

        // İstek parametrelerini ayarlayın.
        $params = [
            'apiKey' => $apiKey,
            'query'  => $query,
            // Örneğin, dönecek tarif sayısını sınırlamak için:
            // 'number' => 10,
        ];

        try {
            // GET isteği gönderin.
            $response = $client->request('GET', $url, [
                'query'  => $params,
                'verify' => false, // SSL doğrulamasını devre dışı bırakır (sadece geliştirme için önerilir)
            ]);



            // Gelen cevabı JSON olarak decode edin.
            $data = json_decode($response->getBody(), true);

            // İsteğe bağlı: Sadece tarifleri (results) döndürmek için
            // $recipes = $data['results'] ?? [];

            return response()->json($data, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'API çağrısı sırasında hata oluştu.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function getRecipeDetails($recipeId)
    {
        $client = new \GuzzleHttp\Client([
            'verify' => false // SSL doğrulamasını kapat
        ]);        $apiKey = env('SPOONACULAR_API_KEY');
        $url = "https://api.spoonacular.com/recipes/{$recipeId}/information?apiKey={$apiKey}";

        try {
            $response = $client->get($url);
            $data = json_decode($response->getBody(), true);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'API çağrısı sırasında hata oluştu.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getMealPlanByWeight($weight)
    {
        $client = new \GuzzleHttp\Client([
            'verify' => false // SSL doğrulamasını kapat
        ]);        $apiKey = env('SPOONACULAR_API_KEY');

        // Kullanıcının kilosuna göre günlük kalori ihtiyacını tahmin et (basit hesaplama)
        $calories = $weight * 30; // Ortalama olarak kilo başına 30 kalori hesapladık

        $url = "https://api.spoonacular.com/mealplanner/generate?apiKey={$apiKey}&targetCalories={$calories}&timeFrame=day";

        try {
            $response = $client->get($url);
            $data = json_decode($response->getBody(), true);

            return response()->json([
                'weight' => $weight . ' kg',
                'daily_calories' => $calories,
                'meal_plan' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'API çağrısı sırasında hata oluştu.', 'message' => $e->getMessage()], 500);
        }
    }


}