<?php

namespace ProcessMaker\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use ProcessMaker\Models\User;

class SanitizeUsernames implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $users;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Collection $users)
    {
        $this->users = $users;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->users as $index => $user) {

            // Store the pre-update username for comparison
            $pre_update_username = $user->username;
            $updated_username = static::filterAndValidateUsername($user->username, $user->id);

            // If it's the same, then it was already valid
            if ($pre_update_username === $updated_username) {
                continue;
            }

            // Set the valid username to the user
            $updated = DB::table('users')->where('id', $user->id)->update([
                'username' => $updated_username,
            ]);

            // Log the result and continue
            if ($updated) {
                // Search through comments and replace the previous username
                // with the recently updated, valid username
                self::findAndReplaceUsernameInComments($pre_update_username, $updated_username);

                // Log the the changes
                logger()->info("Username Updated From ({$pre_update_username}) to ({$updated_username})", [
                    'user_id' => $user->id,
                    'updated_from_username' => $pre_update_username,
                    'updated_to_username' => $updated_username,
                ]);
            }
        }
    }

    /**
     * Search through existing comments for usernames in plain text and swap out
     * the username with disallowed characters with the filtered/validated one
     *
     * @param  string  $previous_username
     * @param  string  $new_username
     *
     * @return void
     */
    public static function findAndReplaceUsernameInComments(string $previous_username, string $new_username)
    {
        if (! self::packageCommentsInstalled()) {
            return;
        }

        $comments_with_username = DB::table('comments')
                                    ->where('body', 'like', "%@{$previous_username}%")
                                    ->select('id', 'body')
                                    ->orderBy('id')
                                    ->get();

        foreach ($comments_with_username as $comment) {
            DB::table('comments')->where('id', $comment->id)->update([
                'body' => str_replace("@{$previous_username} ", "@$new_username ", $comment->body),
            ]);
        }
    }

    /**
     * Provide the user id or User model and receive a validated username string
     *
     * @param  string  $username
     * @param  int  $id
     *
     * @return string
     * @throws \Exception
     */
    public static function filterAndValidateUsername(string $username, int $id): string
    {
        $i = 0;

        $generator = static function () use ($username, &$i): string {
            if (blank($username = mb_ereg_replace('[^\p{L}\p{N}\-_\.\@\+\s]', '', $username))) {
                $username = 'user_'.random_bytes(4);
            }

            return $i++ !== 0 ? $username.$i : $username;
        };

        do {
            // Ensure uniqueness for the username
            $unique_username_query = DB::table('users')
                                       ->where('username', $username = $generator())
                                       ->where('id', '!=', $id)
                                       ->orderBy('id');
        } while ($unique_username_query->exists());

        // Once we know it's a unique, valid
        // username, send it back
        return $username;
    }

    /**
     * ProcessMaker-specific package comments is installed or now
     *
     * @return bool
     */
    public static function packageCommentsInstalled(): bool
    {
        return File::exists(base_path('vendor/processmaker/package-comments'));
    }
}
