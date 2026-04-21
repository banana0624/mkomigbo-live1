<?php
declare(strict_types=1);

if (!function_exists('mk_render_contribution_widget')) {

function mk_render_contribution_widget(array $opts = []): void
{
    $subject = $opts['subject_area'] ?? 'general';
    $pagePath = $_SERVER['REQUEST_URI'] ?? '/';

    if (!function_exists('h')) {
        function h(string $v): string {
            return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
        }
    }
    ?>
    <div style="margin-top:40px;border:1px solid #ddd;padding:20px;">
      <h3>Contribute to this page</h3>

      <form method="post" action="/contribute/submit.php" enctype="multipart/form-data">

        <input type="hidden" name="subject_area" value="<?= h($subject) ?>">
        <input type="hidden" name="page_path" value="<?= h($pagePath) ?>">

        <input type="text" name="website" style="display:none;">

        <p><input type="text" name="contributor_name" placeholder="Your name" required></p>
        <p><input type="email" name="contributor_email" placeholder="Your email" required></p>

        <p>
          <select name="submission_type" required>
            <option value="">Select type</option>
            <option value="comment">Comment</option>
            <option value="correction">Correction</option>
            <option value="criticism">Criticism</option>
          </select>
        </p>

        <p><input type="text" name="title" placeholder="Title" required></p>

        <p>
          <textarea name="message_text" rows="5" placeholder="Write your message..." required></textarea>
        </p>

        <p><input type="file" name="attachment"></p>

        <p>
          <label>
            <input type="checkbox" name="consent" value="1" required>
            I agree
          </label>
        </p>

        <p>
          <button type="submit">Submit</button>
        </p>

      </form>
    </div>
    <?php
}

}