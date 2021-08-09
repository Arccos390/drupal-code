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

/* themes/custom/shipyard/templates/layout/page.html.twig */
class __TwigTemplate_a04da7e04d7869bde577f3d7bf40712a8c7ada54c20a1c12b18abeeb372ec6d0 extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $tags = ["if" => 108];
        $filters = ["escape" => 70];
        $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if'],
                ['escape'],
                []
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
        // line 45
        echo "<div class=\"layout-container\">

    <div class=\"lock hidden\">
    <div class=\"user-popup\">
    
    <h2>Welcome to our new web application “E-Shipyard” !</h2>
    
    <div class='subheader'>You are kindly requested to let us know if you wish to reserve a place in our Yard for the following winter 2019 – 2020:</div>
    <div class='popup-actions-wrapper'>
      <button id='btn-yes' class=\"user-action\">YES</button> 
      <button id='btn-no' class=\"user-action\">NO</button>
      <button id='btn-maybe' class=\"user-action\">MAYBE</button>
    </div>

    <div class=\"popup-misc\">
    <p class=\"popup-misc-explain\">This information is very important as it will allow us to know the number of the new boats we can book for next winter.</p>
    <p class=\"popup-misc-gain-access\">Only after you answer the question, you will <span class=\"bold\">gain full access to your account</span> where you will be able to check our availability in real time and book a date for your next launching or hauling.</p>
    </div>
  </div>
  </div> 


  <header role=\"banner\">
    <input class=\"menu-btn\" type=\"checkbox\" id=\"menu-btn\" />
    <label class=\"menu-icon\" for=\"menu-btn\"><span class=\"navicon\"></span></label>
    <div class=\"site-logo\"><a href=\"/user\"> <img src=\"";
        // line 70
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["logo"] ?? null)), "html", null, true);
        echo "\" /> </a></div>
    <div class=\"nav-right\">
      ";
        // line 72
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "secondary_menu", [])), "html", null, true);
        echo "
    </div>
  </header>

  
  <div id=\"left-sidebar\" class=\"sidebar-menu\">
    <div class=\"sidebar-menu-wrapper\">  
      <div id=\"bsg-menu-routing\">          
        <div id=\"leftmenu\" class=\"sidebar-menu-inner\">        
          ";
        // line 81
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "primary_menu", [])), "html", null, true);
        echo "
        </div>
      </div>
      <footer> <p>";
        // line 84
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(($context["current_date"] ?? null)), "html", null, true);
        echo "</p> </footer>  
    </div>    
  </div>  
  
  <div class=\"main-content-wrapper\">
  
    ";
        // line 95
        echo "
    ";
        // line 97
        echo "    <div id=\"bsg-datalist\" v-if=\"['yachts', 'cradles','owners', 'calendar','pending','shipyard-areas'].includes(\$route.name)\">      
    <main role=\"main\">
        ";
        // line 100
        echo "        <router-view></router-view>        
      </main>
    </div>    
    
    ";
        // line 105
        echo "    <div id=\"general-container\" style='display: none'>
      <main role=\"main\" class='test'>
        <router-view></router-view>
        ";
        // line 108
        if ( !($context["is_front"] ?? null)) {
            // line 109
            echo "          ";
            echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "content", [])), "html", null, true);
            echo "
        ";
        }
        // line 110
        echo "    
      </main>
      <div id=\"right-sidebar\" class=\"right-sidebar\">
          ";
        // line 113
        echo $this->env->getExtension('Drupal\Core\Template\TwigExtension')->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed($this->getAttribute(($context["page"] ?? null), "sidebar_second", [])), "html", null, true);
        echo "      
      </div>
    </div>
  </div>
</div>



";
    }

    public function getTemplateName()
    {
        return "themes/custom/shipyard/templates/layout/page.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  145 => 113,  140 => 110,  134 => 109,  132 => 108,  127 => 105,  121 => 100,  117 => 97,  114 => 95,  105 => 84,  99 => 81,  87 => 72,  82 => 70,  55 => 45,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("", "themes/custom/shipyard/templates/layout/page.html.twig", "/home/bsgeship/public_html/themes/custom/shipyard/templates/layout/page.html.twig");
    }
}
