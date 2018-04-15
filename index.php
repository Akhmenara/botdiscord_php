<?php
require_once(__DIR__.'/vendor/autoload.php');

/*
 * Get bot configurations
 */
$ini = parse_ini_file("config.ini");

$loop = \React\EventLoop\Factory::create();
$client = new \CharlotteDunois\Yasmin\Client(array(), $loop);

$client->on('message', function ($message) use ($client) {
    $ini = parse_ini_file("config.ini");
    try {
        echo $message->author->username . " : " . $message->content . "\n"; // Log messages
        // If message contains "bot"
        if($message->author->username !== $client->user->username && strstr($message->content, "bot")) {
            $m = array("NON JE NE SUIS PAS UN BOT", "Je suis un humain normal comme vous et moi.", 'Ne vous fiez pas à la mention"bot" sur le côté. Je suis humain.',
                        "Je dois bien vous avouer que j'ai quelques problèmes existentiels parce que je n'existe pas.",
                        "Beep-bee-bee-boop-bee-doo-weep hum pardon je m'étais assoupi. Je suis humain.", "Moi un robot ? Haha très drôle !",
                        "Vous allez blesser mon petit coeur d'humain fait de chair en me traitant de robot !");
            $rng = rand(0, count($m)-1);
            // Send a random string
            $message->channel->send($m[$rng]);
        }else{
            // Commands begin with '&'
            if (substr($message->content, 0, 1) === '&') {
                // Explode the string in an array
                // $args[0] : &command
                // $args[1] : arguments given to command
                $args = explode(' ', $message->content, 2);
                // Get the requested command
                switch (substr($args[0], 1)) {
                    case "help":
                        $message->author->dmChannel->send("Je ne peux pas t'aider. Personne ne peut t'aider.");
                        break;
                    case "birth":
                        $timestamp = $message->author->createdTimestamp;
                        $message->reply(" a été créé le " . date("d/m/Y", $timestamp) . " à " . date("H:i:s", $timestamp));
                        break;
                    // ??
                    //case "loop":
                    //    $message->channel->send("&loop");
                    //    break;
                    case "anime":
                        $mal_args = explode(' ', $args[1], 2);
                        $types = array("tv", "special", "movie", "ova");
                        if (empty($args[1])) {
                            // Help message for this command
                            $message->channel->send("Syntax : &anime [TV|Special|Movie|OVA] name");
                        } else if (!in_array(strtolower($mal_args[0]), $types)) { // Check if type has been precised
                            $name = $args[1];
                            // Format name to be able to search it
                            $search = str_replace(' ', '+', $name);
                            $output = "default";
                            // Get MAL credentials
                            $mal_login = $ini['mal_login'];
                            $mal_passwd = $ini['mal_passwd'];
                            // Send request
                            exec("curl -u " . $mal_login . ":" . $mal_passwd . " https://myanimelist.net/api/anime/search.xml?q=$search", $output);

                            // Get XML object from output
                            $animes = simplexml_load_string(implode($output));

                            $ids = array();
                            foreach ($animes as $anime) {
                                    $ids[] = $anime->id;
                            }

                            // Limit results to be shown
                            if (count($ids) >= 3) {
                                $max = 3;
                            } else {
                                $max = count($ids);
                            }

                            // Send request results to channel
                            $message->channel->send("Affichage des $max premières entrées.");
                            for ($i = 0; $i < $max; $i++) {
                                $message->channel->send("https://myanimelist.net/anime/" . $animes->entry[$i]->id);
                            }
                        } else {
                            $type = $mal_args[0];
                            $name = $mal_args[1];
                            // Format name to be able to search it
                            $search = str_replace(' ', '+', $name);
                            $output = "default";
                            // Get MAL credentials
                            $mal_login = $ini['mal_login'];
                            $mal_passwd = $ini['mal_passwd'];
                            exec("curl -u " . $mal_login . ":" . $mal_passwd . " https://myanimelist.net/api/anime/search.xml?q=$search", $output);

                            // Get XML object from output
                            $animes = simplexml_load_string(implode($output));
                            $ids = array();
                            foreach ($animes as $anime) {
                                if (strtolower($anime->type) === strtolower($type)) {
                                    $ids[] = $anime->id;
                                }
                            }

                            // Limit results to be shown
                            if (count($ids) >= 3) {
                                $max = 3;
                            } else {
                                $max = count($ids);
                            }

                            // Send request results to channel
                            $message->channel->send("Affichage des $max premières entrées.");
                            for ($i = 0; $i < $max; $i++) {
                                $message->channel->send("https://myanimelist.net/anime/" . $ids[$i]);
                            }
                        }
                        break;
                    default:
                        // If command not found
                        $message->channel->startTyping();
                        $message->channel->send("...")->otherwise(function ($error) {
                            echo $error . PHP_EOL;
                        });
                        $message->channel->stopTyping(true);
                        sleep(2);
                        $message->channel->startTyping();
                        $message->channel->send("Que les rares personnes qui comprennent " . $message->author->__toString() . " lèvent la main !")->otherwise(function ($error) {
                            echo $error . PHP_EOL;
                        });
                        $message->channel->stopTyping(true);
                }
            }
        }
    } catch(\Exception $error) {
        // Handle exception
    }
});

$client->once('ready', function () use ($client) {
    $client->user->setGame('faire chier le monde.');
});
$client->login($ini['bot_token']);
// Log bot start
echo "Bot started\n";
// Run  bot
$loop->run();
