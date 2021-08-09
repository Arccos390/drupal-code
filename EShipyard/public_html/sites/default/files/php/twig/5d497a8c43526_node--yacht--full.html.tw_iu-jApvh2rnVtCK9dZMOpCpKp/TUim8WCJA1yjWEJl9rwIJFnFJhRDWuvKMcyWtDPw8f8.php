<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* themes/custom/shipyard/templates/content/node--yacht--full.html.twig */
class __TwigTemplate_88785c32587e1139c6ef52d31f8a8270f64633b629a6738c69d490e991125eb4 extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $tags = ["set" => 74];
        $filters = ["clean_class" => 76, "escape" => 83];
        $functions = ["attach_library" => 83];

        try {
            $this->sandbox->checkSecurity(
                ['set'],
                ['clean_class', 'escape'],
                ['attach_library']
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->getSourceContext());

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 74
        $context["classes"] = [0 => "node", 1 => ("node--type-" . \Drupal\Component\Utility\Html::getClass($this->sandbox->ensureToStringAllowed($this->getAttribute(        // line 76
($context["node"] ?? null), "bundle", [])))), 2 => (($this->getAttribute(        // line 77
($context["node"] ?? null), "isPromoted", [], "method")) ? ("node--promoted") : ("")), 3 => (($this->getAttribute(        // line 78
($context["node"] ?? null), "isSticky", [], "method")) ? ("node--sticky") : ("")), 4 => (( !$this->getAttribute(        // line 79
($context["node"] ?? null), "isPublished", [], "method")) ? ("node--unpublished") : ("")), 5 => ((        // line 80
($context["view_mode"] ?? null)) ? (("node--view-mode-" . \Drupal\Component\Utility\Html::getClass($this->sandbox->ensureToStringAllowed(($context["view_mode"] ?? null))))) : (""))];
        // line 83
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->env->getExtension('Drupal\Core\Template\TwigExtension')->attachLibrary("shipyard/node"), "html", null, true);
        echo "


<article";
        // line 86
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["attributes"] ?? null), "addClass", [0 => ($context["classes"] ?? null)], "method")), "html", null, true);
        echo ">

  <div id=\"bsg-yacht\" :data-id=\"";
        // line 88
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["node"] ?? null), "id", [])), "html", null, true);
        echo "\" v-if=\"!['yachts', 'cradles', 'owners'].includes(\$route.name)\">
    
    ";
        // line 101
        echo "
    

    <bsg-yacht v-if=\"nodeData[0] && nodeData[0].title\" :data=\"nodeData\"></bsg-yacht>
  
    <bsg-yacht-requests v-if=\"nodeData[0] && nodeData[0].title\" :data=\"nodeData\"></bsg-yacht-requests>

    <bsg-yacht-inf v-if=\"nodeData[0]\" :data=\"nodeData\"></bsg-yacht-inf>

    

    ";
        echo "

  </div>

</article>















";
    }

    public function getTemplateName()
    {
        return "themes/custom/shipyard/templates/content/node--yacht--full.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  78 => 101,  73 => 88,  68 => 86,  62 => 83,  60 => 80,  59 => 79,  58 => 78,  57 => 77,  56 => 76,  55 => 74,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("", "themes/custom/shipyard/templates/content/node--yacht--full.html.twig", "/home/bsgeship/public_html/themes/custom/shipyard/templates/content/node--yacht--full.html.twig");
    }
}
