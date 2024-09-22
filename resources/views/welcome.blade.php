<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>AI App!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        pre {
            font-family: unset;
            text-wrap: unset;
        }

        .max-w-75 {
            max-width: 75%;
        }
    </style>
</head>
<body>
    <div class="container-fuild bg-light min-vh-100">
        <x-navbar />

        <div class="container my-2 max-w-75 mx-auto">
            <div class="card">
                <div class="output-container card-body mb-2 overflow-auto" style="height:calc(100vh - 235px);">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="conversation"></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-12">
                            <form class="generate-story-form d-flex flex-column">
                                @csrf
                                <textarea class="form-control" style="resize: none" placeholder="Give a topic and AI will generate a story for you..." name="generateText" cols="30" rows="3"></textarea>
                                <button class="btn btn-dark mt-2 ms-auto">Generate</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/js/all.min.js" integrity="sha512-6sSYJqDreZRZGkJ3b+YfdhB3MzmuP9R7X1QZ6g5aIXhRvR1Y/N/P47jmnkENm7YL3oqsmI6AK+V6AD99uWDnIw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/showdown/2.0.0/showdown.min.js"></script>

    <script>
        $(document).ready(function() {
            $(".generate-story-form").on("submit", function(e) {
                e.preventDefault();
                const $form = $(this);
                const $btn = $form.find(":submit");
                const btnHtml = $btn.html();

                // Disable the button during request
                $form.find("[name='generateText']").prop("disabled", true);
                $btn.html("<span class='spinner-grow spinner-grow-sm'></span>").prop("disabled", true);

                // Reset the output container
                $(".output-container").removeClass("d-none");
                $(".conversation").append("<div class='prompt bg-light rounded max-w-75 w-100 p-2 mb-2 border ms-auto'>" + $form.find("[name='generateText']").val() + "</div>");

                $(".conversation").append("<pre class='prompt-response bg-light rounded max-w-75 w-100 p-2 mb-2 border'></pre>")
                $(".conversation").find(".prompt-response").last().append("<span class='loading-response'><span class='spinner-grow spinner-grow-sm'></span></span>");

                $(".output-container").find(".alert").remove();

                let scrollTo = $(".conversation").find(".prompt-response").last()[0].scrollTop;
                $(".output-container").scrollTop(scrollTo);

                // Create a new EventSource for the stream
                const data = {
                    generateText: $form.find("[name='generateText']").val(),
                }
                // let eventSource = new EventSource("{{ route('generate-text') }}?" + formData);

                // Send the POST request with fetch for SSE
                fetch('/generate-text', {
                    method: 'POST',
                    headers: {
                        'Accept': 'text/event-stream', // Expecting a text/event-stream response
                        'Content-Type': 'application/json', // Sending JSON data
                        'X-CSRF-TOKEN': "{{ csrf_token() }}", // CSRF Token for Laravel security
                    },
                    body: JSON.stringify(data), // Send form data directly as JSON
                })
                .then(function (response) {
                    // Check for validation errors
                    if (!response.ok) {
                        $(".conversation .loading-response").remove();

                        if (response.status === 422) {
                            return response.json().then(function (errors) {
                                console.log('Validation errors:', errors);
                                for (let field in errors) {
                                    $("<div class='alert alert-danger'>" + errors[field] + "</div>").insertAfter($(".conversation"));
                                }
                            });
                        } else {
                            return response.text().then(function (text) {
                                console.error('Error:', text);
                            });
                        }
                    }

                    return response.body;
                })
                .then(response => {
                    const reader = response.getReader();
                    const decoder = new TextDecoder("utf-8");

                    let prevLastLine = "";

                    function readStream() {
                        $(".conversation").find(".prompt-response").last().append("<span class='loading-response'> <span class='spinner-grow spinner-grow-sm'></span></span>");

                        return reader.read().then(({ done, value }) => {
                            if (done) {
                                console.log('Stream finished.');
                                $(".conversation").find(".loading-response").remove();

                                // Convert the markdown to HTML
                                const converter = new showdown.Converter();
                                let text = $(".conversation").find(".prompt-response").last().text();
                                let html = converter.makeHtml(text);
                                $(".conversation").find(".prompt-response").last().html(html);
                                return;
                            }

                            // Decode the chunk into text
                            const chunk = decoder.decode(value, { stream: true });

                            // Split the chunk by newlines (SSE uses `\n\n` for each event)
                            const lines = chunk.split("\n\n");

                            // get last line
                            let firstLine = lines[0];
                            let lastLine = lines[lines.length - 1];

                            if (!firstLine.startsWith("data: ")) {
                                lines[0] = prevLastLine + firstLine;
                            }

                            try {
                                JSON.parse(lastLine.replace("data: ", ""));
                                prevLastLine = '';
                            } catch (error) {
                                prevLastLine = lastLine;
                                lines.pop();
                            }

                            // Process each line to see if it's a data event
                            lines.forEach((line, k) => {
                                $(".conversation").find(".loading-response").first().remove();
                                if (line.startsWith("data: ")) {
                                    let buffer = line.replace("data: ", ""); // Add the current chunk to the buffer

                                    if (buffer === "[DONE]") {
                                        console.log("Stream completed");
                                        $form.find("[name='generateText']").val('');
                                    } else {
                                        // Parse the JSON data
                                        try {
                                            const parsedData = JSON.parse(buffer);

                                            if (parsedData.response) {
                                                // Append the response text to the output element
                                                $(".conversation").find(".prompt-response").last().append(parsedData.response);
                                            }
                                        } catch (error) {
                                            console.error("Error parsing chunk:", error);
                                        }

                                        $(".conversation .loading-response").remove();
                                    }
                                }
                            });

                            // Recursively read the next chunk
                            return readStream();
                        });
                    }

                    readStream();
                })
                .catch(function (error) {
                    console.error('Error:', error);
                })
                .finally(function () {
                    // Re-enable the buttons and restore the UI
                    $form.find("[name='generateText']").prop("disabled", false);
                    $btn.html(btnHtml).prop("disabled", false);
                    $(".conversation .loading-response").remove();
                });
            });
        });
    </script>
</body>
</html>
