<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Diagnostic\Consultation;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConsultationPolicy
{
    use HandlesAuthorization;
    
    public function viewClient(User $user, Consultation $consultation): bool
    {
        return $consultation->user_id === $user->id || $user->is_admin;
    }
    
    public function viewExpert(User $user, Consultation $consultation): bool
    {
        // Эксперт может смотреть консультации, которые ведет или которые назначены ему
        return ($consultation->expert_id === $user->id && $user->is_expert) 
            || ($consultation->status === 'scheduled' && $user->is_expert)
            || $user->is_admin;
    }
    
    public function start(User $user, Consultation $consultation): bool
    {
        // Эксперт может начать консультацию, если она запланирована и нет эксперта
        return $user->is_expert 
            && $consultation->status === 'scheduled' 
            && !$consultation->expert_id;
    }
    
    public function sendMessage(User $user, Consultation $consultation): bool
    {
        // Участники консультации могут отправлять сообщения
        return in_array($consultation->status, ['in_progress', 'scheduled'])
            && ($consultation->user_id === $user->id 
                || $consultation->expert_id === $user->id
                || $user->is_admin);
    }
    
    public function addAnalysis(User $user, Consultation $consultation): bool
    {
        // Только ведущий эксперт может добавлять анализ
        return $user->is_expert 
            && $consultation->expert_id === $user->id
            && $consultation->status === 'in_progress';
    }
    
    public function requestData(User $user, Consultation $consultation): bool
    {
        // Эксперт может запрашивать данные во время консультации
        return $user->is_expert 
            && $consultation->expert_id === $user->id
            && in_array($consultation->status, ['in_progress', 'scheduled']);
    }
    
    public function complete(User $user, Consultation $consultation): bool
    {
        // Только ведущий эксперт может завершить консультацию
        return $user->is_expert 
            && $consultation->expert_id === $user->id
            && $consultation->status === 'in_progress';
    }
    
    public function addFeedback(User $user, Consultation $consultation): bool
    {
        // Клиент может добавить отзыв после завершения
        return $consultation->user_id === $user->id
            && $consultation->status === 'completed'
            && !$consultation->rating;
    }
    
    public function uploadFile(User $user, Consultation $consultation): bool
    {
        // Участники могут загружать файлы во время активной консультации
        return in_array($consultation->status, ['in_progress', 'scheduled'])
            && ($consultation->user_id === $user->id 
                || $consultation->expert_id === $user->id);
    }
    
    // Проверка роли эксперта
    public function expert(User $user): bool
    {
        return $user->is_expert || $user->is_admin;
    }
}