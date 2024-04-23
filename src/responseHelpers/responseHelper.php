<?php
namespace ClaudeToGPTAPI\ResponseHelpers;

use ClaudeToGPTAPI\Models;
use stdClass;

/**
 * Converts a response from the Claude API into an object format similar to a ChatGPT response.
 *
 * @param stdClass $claudeResponse Response object from the Claude API.
 * @param bool $stream Indicates if the response is from a streaming endpoint.
 * @return stdClass Formatted response similar to ChatGPT API responses.
 * @throws Exception If the response format is invalid.
 */
function claudeToChatGPTResponse(stdClass $claudeResponse, bool $stream = false): stdClass
{
    // Retrieve the stop reason map from a model or similar source
    $stopReasonMap = Models::getStopReasonMap();

    $completion = $claudeResponse->completion;
    $timestamp = time(); // Current time as Unix timestamp
    $completionTokens = count(explode(" ", $completion)); // Counting tokens based on spaces

    $result = new stdClass();
    $result->id = "chatcmpl-" . $timestamp;
    $result->created = $timestamp;
    $result->model = "gpt-3.5-turbo-0613";
    $result->usage = new stdClass();
    $result->usage->prompt_tokens = 0;
    $result->usage->completion_tokens = $completionTokens;
    $result->usage->total_tokens = $completionTokens;
    $result->choices = []; // Initialize choices array to hold choice objects

    $choice = new stdClass();
    $choice->index = 0;
    // Use the stop reason map to resolve the stop reason if it exists
    $choice->finish_reason = isset($claudeResponse->stop_reason) ? $stopReasonMap[$claudeResponse->stop_reason] : null;

    $message = new stdClass();
    $message->role = "assistant";
    $message->content = $completion;

    if (!$stream) {
        $result->object = "chat.completion";
        $choice->message = $message; // Assigning message object to choice if not streaming
    } else {
        $result->object = "chat.completion.chunk";
        $choice->delta = $message; // Assigning message object to delta if streaming
    }

    // Push the prepared choice object into the choices array
    array_push($result->choices, $choice);

    return $result;
}

