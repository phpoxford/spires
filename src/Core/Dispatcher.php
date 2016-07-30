<?php
declare(strict_types=1);

namespace Spires\Core;

use Spires\Irc\Client;
use Spires\Irc\Message\Inbound\RawMessage;
use Spires\Contracts\Core\Core as SpiresCore;

class Dispatcher
{
    /**
     * @var Core
     */
    private $core;

    /**
     * @var int
     */
    private $indent = 0;

    public function __construct(SpiresCore $core)
    {
        $this->core = $core;
    }

    public function dispatch($message)
    {
        $this->debug('━ Dispatching: [' . get_class($message) . ']');
        $this->core->instance(get_class($message), $message);
        $this->indent++;
        $enrichedMessages = [];
        foreach ($this->core->getPlugins() as $pluginClass => $pluginObject) {
            $methods = $this->getPluginMethods($pluginClass);

            foreach ($methods as $method) {
                if ($this->acceptsMessage($method, $message)) {
                    $this->debug('├ Calling: [' . $pluginClass . '@' . $method->getName() . ']');
                    $enrichedMessage = $this->core->call([$pluginObject, $method->name], [$message]);
                    if (!is_null($enrichedMessage)) {
                        $this->debug('├── Enriched message: [' . get_class($enrichedMessage) . ']');
                        $enrichedMessages[] = $enrichedMessage;
                    }
                }
            }
        }

        $this->debug('┕ Number of enriched messages to dispatch: ' . count($enrichedMessages));
        $this->indent++;
        foreach ($enrichedMessages as $enrichedMessage) {
            $this->dispatch($enrichedMessage);
        }
        $this->indent--;
        $this->indent--;
    }

    private function acceptsMessage(\ReflectionMethod $method, RawMessage $message) : bool
    {
        $parameters = $method->getParameters();
        foreach ($parameters as $parameter) {
            if ($parameter->getClass() && $parameter->getClass()->name === get_class($message)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $pluginClass
     * @return \ReflectionMethod[]
     */
    private function getPluginMethods(string $pluginClass) : array
    {
        return $this->removeUnsupportedMethods(
            (new \ReflectionClass($pluginClass))->getMethods()
        );
    }

    /**
     * @param \ReflectionMethod[] $methods
     * @return \ReflectionMethod[]
     */
    private function removeUnsupportedMethods(array $methods) : array
    {
        return array_filter($methods, function (\ReflectionMethod $method) {
            return $method->isPublic() && !$method->isStatic();
        });
    }

    private function debug(string $message)
    {
        $indention = str_repeat(' ', $this->indent*2);
        $this->core->make(Client::class)->logDebug($indention . $message);
    }
}
